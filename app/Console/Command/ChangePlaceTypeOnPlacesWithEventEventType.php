<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Offer\Commands\UpdateType;
use CultuurNet\UDB3\Search\ResultsGenerator;
use CultuurNet\UDB3\Search\SearchServiceInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

final class ChangePlaceTypeOnPlacesWithEventEventType extends AbstractCommand
{
    private SearchServiceInterface $searchService;

    private int $errors;

    private int $success;

    public function __construct(
        CommandBus $commandBus,
        SearchServiceInterface $searchService
    ) {
        $this->searchService = $searchService;
        $this->errors = 0;
        $this->success = 0;
        parent::__construct($commandBus);
    }

    public function configure(): void
    {
        $this->setName('place:faulty-eventtype:update');

        $this->setDescription(
            'Updates all places with an Event eventType'
        );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);
        $logger = new ConsoleLogger($output);

        $mapping = $this->getMapping();
        /** @var string $faultyEventType */
        foreach ($mapping as $faultyEventType => $correctEventType) {
            $resultsGenerator = new ResultsGenerator(
                $this->searchService,
                ['created' => 'asc'],
                100
            );

            $query = 'terms.id:' . $faultyEventType;
            if (!$this->askConfirmation($input, $output, $resultsGenerator->count($query), $faultyEventType, $correctEventType)) {
                return 0;
            }

            $places = $resultsGenerator->search($query);

            foreach ($places as $place) {
                $placeId = $place->getId();
                try {
                    $this->commandBus->dispatch(new UpdateType($placeId, $correctEventType));
                    $logger->info(
                        'Successfully changed type of place "' . $placeId . '" to  "' . $correctEventType . '"'
                    );
                    $this->success++;
                } catch (\Throwable $t) {
                    $logger->error(
                        sprintf(
                            'An error occurred while changing type of place "%s": %s with message %s',
                            $placeId,
                            get_class($t),
                            $t->getMessage()
                        )
                    );
                    $this->errors++;
                }
            }

            $logger->info('Successfully changed the eventType of  ' . $this->success . ' places');

            if ($this->errors) {
                $logger->error('Failed to change type of ' . $this->errors . ' places');
            }
        }
        return $this->errors;
    }

    private function askConfirmation(
        InputInterface $input,
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
                    "This action will change {$faultyEventType} to {$correctEventType} on {$count} places, continue? [y/N] ",
                    false
                )
            );
    }

    private function getMapping(): array
    {
        return [
            '0.50.4.0.0' => 'OyaPaf64AEmEAYXHeLMAtA',
            '0.54.0.0.0' => '8.70.0.0.0',
            '0.5.0.0.0' => '0.53.0.0.0',
            '0.50.6.0.0' => 'BtVNd33sR0WntjALVbyp3w',
            '0.3.2.0.0' => 'Yf4aZBfsUEu2NsQqsprngw',
            '0.55.0.0.0' => '8.70.0.0.0',
            '0.19.0.0.0' => 'eBwaUAAhw0ur0Z02i5ttnw',
            '0.37.0.0.0' => '0.8.0.0.0',
            '0.12.0.0.0' => '0.53.0.0.0',
            '0.6.0.0.0' => 'OyaPaf64AEmEAYXHeLMAtA',
            '0.0.0.0.0' => 'GnPFp9uvOUyqhOckIFMKmg',
            '0.7.0.0.0' => 'GnPFp9uvOUyqhOckIFMKmg',
            '0.3.1.0.0' => 'rJRFUqmd6EiqTD4c7HS90w',
            '0.3.1.0.1' => 'rJRFUqmd6EiqTD4c7HS90w',
            '0.57.0.0.0' => 'JCjA0i5COUmdjMwcyjNAFA',
            '0.28.0.0.0' => 'OyaPaf64AEmEAYXHeLMAtA',
            '0.17.0.0.0' => '0.8.0.0.0',
            '0.49.0.0.0' => 'YVBc8KVdrU6XfTNvhMYUpg',
            '1.50.0.0.0' => 'OyaPaf64AEmEAYXHeLMAtA',
            '0.50.21.0.0' => 'Yf4aZBfsUEu2NsQqsprngw',
            '0.59.0.0.0' => 'eBwaUAAhw0ur0Z02i5ttnw',
            '0.100.0.0.0' => 'Yf4aZBfsUEu2NsQqsprngw',
            '0.100.1.0.0' => '0.53.0.0.0',
            '0.100.2.0.0' => '0.53.0.0.0',
            '0.51.0.0.0' => 'Yf4aZBfsUEu2NsQqsprngw',
        ];
    }
}
