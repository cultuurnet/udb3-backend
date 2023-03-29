<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Event\Events\AvailableFromUpdated;
use CultuurNet\UDB3\Offer\Commands\UpdateType;
use CultuurNet\UDB3\Search\ResultsGenerator;
use CultuurNet\UDB3\Search\SearchServiceInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

final class ChangePlaceTypeOnEvents extends AbstractCommand
{
    private SearchServiceInterface $searchService;

    private int $errorCount;

    private int $successCount;

    public function __construct(
        CommandBus $commandBus,
        SearchServiceInterface $searchService
    ) {
        $this->searchService = $searchService;
        $this->errorCount = 0;
        $this->successCount = 0;
        parent::__construct($commandBus);
    }

    public function configure(): void
    {
        $this->setName('event:place-eventtype:update');

        $this->setDescription(
            'Updates all events with an Place eventType and set availableFrom far in the future'
        );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);
        $logger = new ConsoleLogger($output);

        $correctPlaceTypeMapping = $this->getCorrectPlaceTypeMapping();

        foreach ($correctPlaceTypeMapping as $placeType => $correctEventType) {
            $resultsGenerator = new ResultsGenerator(
                $this->searchService,
                ['created' => 'asc'],
                100
            );

            $query = 'terms.id:' . $placeType;
            if (!$this->askConfirmation($input, $output, $resultsGenerator->count($query), $placeType, $correctEventType)) {
                return 0;
            }

            $events = $resultsGenerator->search($query);

            foreach ($events as $event) {
                $eventId = $event->getId();
                try {
                    $this->commandBus->dispatch(new UpdateType($eventId, $correctEventType));
                    $this->commandBus->dispatch(new AvailableFromUpdated($eventId, \DateTimeImmutable::createFromFormat('Y-m-d', '2100-01-01')));
                    $logger->info(
                        'Successfully changed type and availableFrom of event "' . $eventId . '" to  "' . $correctEventType . '"'
                    );
                    $this->successCount++;
                } catch (\Throwable $t) {
                    $logger->error(
                        sprintf(
                            'An error occurred while changing type of event "%s": %s with message %s',
                            $eventId,
                            get_class($t),
                            $t->getMessage()
                        )
                    );
                    $this->errorCount++;
                }
            }

            $logger->info('Successfully changed the eventType of  ' . $this->successCount . ' events');

            if ($this->errorCount) {
                $logger->error('Failed to change type of ' . $this->errorCount . ' events');
            }
        }
        return $this->errorCount;
    }

    private function askConfirmation(
        InputInterface  $input,
        OutputInterface $output,
        int $count,
        string $faultyEventType,
        string $correctEventType
    ): bool {
        return $this
            ->getHelper('question')
            ->ask(
                $input,
                $output,
                new ConfirmationQuestion(
                    "This action will change {$faultyEventType} to {$correctEventType} on {$count} events, continue? [y/N] ",
                    false
                )
            );
    }

    private function getCorrectPlaceTypeMapping(): array
    {
        return [
            'kI7uAyn2uUu9VV6Z3uWZTA' => '0.3.1.0.0',
            'BtVNd33sR0WntjALVbyp3w' => '0.50.6.0.0',
            'Yf4aZBfsUEu2NsQqsprngw' => '0.12.0.0.0',
            'YVBc8KVdrU6XfTNvhMYUpg' => '0.49.0.0.0',
            'ekdc4ATGoUitCa0e6me6xA' => '0.28.0.0.0',
            'JCjA0i5COUmdjMwcyjNAFA' => '0.3.1.0.1',
            '0.14.0.0.0' => '0.7.0.0.0',
            'GnPFp9uvOUyqhOckIFMKmg' => '0.0.0.0.0',
            '0.15.0.0.0' => '0.17.0.0.0',
            '0.8.0.0.0' => '0.28.0.0.0',
            '0.53.0.0.0' => '0.12.0.0.0',
            'rJRFUqmd6EiqTD4c7HS90w' => '0.12.0.0.0',
            'eBwaUAAhw0ur0Z02i5ttnw' => '0.59.0.0.0',
            '8.70.0.0.0' => '0.55.0.0.0',
            '0.41.0.0.0' => '0.28.0.0.0',
            'VRC6HX0Wa063sq98G5ciqw' => '0.12.0.0.0',
            'OyaPaf64AEmEAYXHeLMAtA' => '0.6.0.0.0',
        ];
    }
}
