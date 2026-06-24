<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\User\ManagementToken\ManagementTokenGenerator;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
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
 * For every id in the given file the command does a GET on the Entry API. An event is only updated when:
 *  - the top-level bookingInfo URL is empty (null/absent), AND
 *  - every sub-event has a non-empty bookingInfo URL, AND
 *  - all those sub-event URLs are equal.
 *
 * When updating, the existing top-level phone and email are preserved so they can never be overwritten by accident.
 * Rejected ids are always written to the log file together with the reason they were skipped.
 */
final class FixMultipleEventBookingInfo extends Command
{
    private ClientInterface $httpClient;
    private ManagementTokenGenerator $tokenGenerator;
    private string $baseUrl;
    private LoggerInterface $logger;

    public function __construct(
        ClientInterface $httpClient,
        ManagementTokenGenerator $tokenGenerator,
        string $baseUrl,
        LoggerInterface $logger
    ) {
        parent::__construct();
        $this->httpClient = $httpClient;
        $this->tokenGenerator = $tokenGenerator;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->logger = $logger;
    }

    protected function configure(): void
    {
        $this
            ->setName('fix-multiple-event-bookinginfo')
            ->setDescription(
                'Copies the sub-event bookingInfo URL to the top level for events with an empty top-level URL and ' .
                'identical sub-event URLs (Entry API).'
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
                'Do not perform any PUT. Only log the ids that would have been changed.'
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

        $token = $this->tokenGenerator->newToken()->getToken();

        $output->writeln(($dryRun ? '<comment>[DRY-RUN] </comment>' : '') . 'Processing ' . count($ids) . ' events against ' . $this->baseUrl);

        $updated = 0;
        $rejected = 0;
        $failed = 0;

        $progressBar = new ProgressBar($output, count($ids));
        $progressBar->start();

        foreach ($ids as $id) {
            try {
                $event = $this->getEvent($token, $id);

                $reason = $this->reasonToReject($event);
                if ($reason !== null) {
                    $this->logger->info('REJECTED {id}: {reason}', ['id' => $id, 'reason' => $reason]);
                    $rejected++;
                    continue;
                }

                $body = $this->buildBookingInfo($event);

                if ($dryRun) {
                    $this->logger->info('WOULD UPDATE {id} with url {url}', ['id' => $id, 'url' => $body['url']]);
                    $updated++;
                    continue;
                }

                $this->updateBookingInfo($token, $id, $body);
                $this->logger->info('UPDATED {id} with url {url}', ['id' => $id, 'url' => $body['url']]);
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
     * @return array<string, mixed>
     */
    private function getEvent(string $token, string $id): array
    {
        $request = new Request(
            'GET',
            $this->baseUrl . '/events/' . $id,
            [
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ]
        );

        $response = $this->httpClient->sendRequest($request);
        $status = $response->getStatusCode();
        if ($status !== 200) {
            throw new RuntimeException('GET returned HTTP ' . $status);
        }

        return Json::decodeAssociatively($response->getBody()->getContents());
    }

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

        if (!isset($event['subEvent'][0]['bookingInfo']['urlLabel'])) {
            return 'sub-event URL has no urlLabel to copy';
        }

        return null;
    }

    private function buildBookingInfo(array $event): array
    {
        $bookingInfo = $event['bookingInfo'] ?? [];
        if (!is_array($bookingInfo)) {
            $bookingInfo = [];
        }

        $subBookingInfo = $event['subEvent'][0]['bookingInfo'];

        // Never carry over a stale/empty url; rebuild it explicitly from the sub-event.
        unset($bookingInfo['url'], $bookingInfo['urlLabel']);

        $bookingInfo['url'] = $subBookingInfo['url'];
        $bookingInfo['urlLabel'] = $subBookingInfo['urlLabel'];

        return $bookingInfo;
    }

    /**
     * @param array<string, mixed> $bookingInfo
     */
    private function updateBookingInfo(string $token, string $id, array $bookingInfo): void
    {
        $request = new Request(
            'PUT',
            $this->baseUrl . '/events/' . $id . '/booking-info/',
            [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            Json::encode($bookingInfo)
        );

        $response = $this->httpClient->sendRequest($request);
        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            throw new RuntimeException('PUT returned HTTP ' . $status . ': ' . $response->getBody()->getContents());
        }
    }
}
