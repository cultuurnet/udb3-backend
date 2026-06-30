<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Event\Commands\UpdateBookingInfo;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Contact\BookingInfoDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Contact\BookingInfo;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * TEMPORARY CODE - CAN BE REMOVED AFTER RUNNING FIX
 *
 * Copies the booking-info URL of the sub-events up to the top level for events that have an empty top-level URL but
 * identical sub-event URLs.
 *
 * For every id in the given file the command reads the event from the JSON-LD read model. An event is only updated when:
 *  - the top-level bookingInfo URL is empty (null/absent), AND
 *  - every sub-event has a non-empty bookingInfo URL, AND
 *  - all those sub-event URLs are equal.
 *
 * Promotion happens on URL match alone. The URL group is then filled in from the first sub-event: the url plus its
 * urlLabel (the call-to-action text) and availabilityStarts/availabilityEnds (when the booking link is shown/clickable),
 * because those all control how and when the url is rendered on UiV, widgets and other output channels.
 *
 * A value that is already set at the top level is never overwritten: only empty top-level fields are filled from the
 * sub-event. In practice the url is always filled (it is empty by definition), while phone, email and any already-set
 * urlLabel or availability dates are left untouched.
 *
 * Updates are dispatched as an UpdateBookingInfo command on the command bus (the CLI runs as the system user, see
 * bin/udb3.php). Rejected ids are always logged together with the reason they were skipped.
 */
final class FixMultipleEventBookingInfo extends AbstractCommand
{
    private DocumentRepository $eventDocumentRepository;
    private LoggerInterface $logger;
    private BookingInfoDenormalizer $bookingInfoDenormalizer;

    public function __construct(
        CommandBus $commandBus,
        DocumentRepository $eventDocumentRepository,
        LoggerInterface $logger
    ) {
        parent::__construct($commandBus);
        $this->eventDocumentRepository = $eventDocumentRepository;
        $this->logger = $logger;
        $this->bookingInfoDenormalizer = new BookingInfoDenormalizer();
    }

    protected function configure(): void
    {
        $this
            ->setName('fix-multiple-event-bookinginfo')
            ->setDescription(
                'Copies the sub-event bookingInfo URL to the top level for events with an empty top-level URL and ' .
                'identical sub-event URLs.'
            )
            ->addArgument(
                'file',
                InputArgument::OPTIONAL,
                'Path to a file containing one event id per line.',
                'ids.txt'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Do not dispatch any command. Only log the ids that would have been changed.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dryRun = (bool) $input->getOption('dry-run');

        $ids = $this->readIds((string) $input->getArgument('file'));
        if ($ids === []) {
            $output->writeln('<error>No event ids found in the given file.</error>');
            return self::FAILURE;
        }

        $output->writeln(($dryRun ? '<comment>[DRY-RUN] </comment>' : '') . 'Processing ' . count($ids) . ' events');

        $updated = 0;
        $rejected = 0;
        $failed = 0;

        $progressBar = new ProgressBar($output, count($ids));
        $progressBar->start();

        foreach ($ids as $id) {
            try {
                $event = $this->eventDocumentRepository->fetch($id)->getAssocBody();

                $reason = $this->reasonToReject($event);
                if ($reason !== null) {
                    $this->logger->info('REJECTED {id}: {reason}', ['id' => $id, 'reason' => $reason]);
                    $rejected++;
                    continue;
                }

                $bookingInfoData = $this->buildBookingInfoData($event);

                if ($dryRun) {
                    $this->logger->info('WOULD UPDATE {id} with url {url}', ['id' => $id, 'url' => $bookingInfoData['url']]);
                    $updated++;
                    continue;
                }

                $this->commandBus->dispatch(new UpdateBookingInfo($id, $this->toBookingInfo($bookingInfoData)));
                $this->logger->info('UPDATED {id} with url {url}', ['id' => $id, 'url' => $bookingInfoData['url']]);
                $updated++;
            } catch (Throwable $e) {
                $this->logger->error('FAILED {id}: {error}', ['id' => $id, 'error' => $e->getMessage()]);
                $failed++;
            } finally {
                $progressBar->advance();
            }
        }

        $progressBar->finish();

        $output->writeln('');
        $output->writeln(sprintf(
            '<info>Done.</info> %s: %d, rejected: %d, failed: %d.',
            $dryRun ? 'Would update' : 'Updated',
            $updated,
            $rejected,
            $failed
        ));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return string[]
     */
    private function readIds(string $file): array
    {
        if (!is_file($file)) {
            throw new RuntimeException('File not found: ' . $file);
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            throw new RuntimeException('Could not read file: ' . $file);
        }

        return array_values(array_filter(array_map('trim', $lines), static fn (string $line) => $line !== ''));
    }

    /**
     * @param array<string, mixed> $event
     */
    private function reasonToReject(array $event): ?string
    {
        $topUrl = $event['bookingInfo']['url'] ?? null;
        if (is_string($topUrl) && trim($topUrl) !== '') {
            return 'top-level URL already filled in (' . $topUrl . ')';
        }

        $subEvents = $event['subEvent'] ?? [];
        if (!is_array($subEvents) || $subEvents === []) {
            return 'no sub-events with a URL';
        }

        $urls = [];
        foreach ($subEvents as $subEvent) {
            $url = $subEvent['bookingInfo']['url'] ?? null;
            if (!is_string($url) || trim($url) === '') {
                return 'not every sub-event has a URL';
            }
            $urls[] = $url;
        }

        if (count(array_unique($urls)) > 1) {
            return 'sub-events have different URLs (' . implode(', ', array_unique($urls)) . ')';
        }

        // A URL can only be set together with a urlLabel (see the bookingInfo JSON schema), so we need one to copy.
        if (!isset($event['subEvent'][0]['bookingInfo']['urlLabel'])) {
            return 'sub-event URL has no urlLabel to copy';
        }

        return null;
    }

    /**
     * @param array<string, mixed> $event
     * @return array<string, mixed>
     */
    private function buildBookingInfoData(array $event): array
    {
        $bookingInfo = $event['bookingInfo'] ?? [];
        if (!is_array($bookingInfo)) {
            $bookingInfo = [];
        }

        foreach ($this->bookingUrlGroup($event['subEvent'][0]['bookingInfo']) as $key => $value) {
            if ($this->isEmptyValue($bookingInfo[$key] ?? null)) {
                $bookingInfo[$key] = $value;
            }
        }

        return $bookingInfo;
    }


    private function isEmptyValue(mixed $value): bool
    {
        if (is_string($value)) {
            return trim($value) === '';
        }

        if (is_array($value)) {
            return $value === [];
        }

        return $value === null;
    }

    /**
     * @param array<string, mixed> $bookingInfo
     * @return array<string, mixed>
     */
    private function bookingUrlGroup(array $bookingInfo): array
    {
        return array_filter(
            [
                'url' => $bookingInfo['url'] ?? null,
                'urlLabel' => $bookingInfo['urlLabel'] ?? null,
                'availabilityStarts' => $bookingInfo['availabilityStarts'] ?? null,
                'availabilityEnds' => $bookingInfo['availabilityEnds'] ?? null,
            ],
            static fn ($value) => $value !== null
        );
    }

    /**
     * @param array<string, mixed> $bookingInfoData
     */
    private function toBookingInfo(array $bookingInfoData): BookingInfo
    {
        return $this->bookingInfoDenormalizer->denormalize($bookingInfoData, BookingInfo::class);
    }
}
