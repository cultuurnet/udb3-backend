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

final class ChangePlaceType extends AbstractCommand
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
        $this->setName('place:actortype:update');

        $this->setDescription(
            'Updates all places with an actor type with their corresponding event type'
        );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);
        $logger = new ConsoleLogger($output);

        $mapping = $this->getMapping();
        /** @var string $actorType */
        foreach ($mapping as $actorType => $eventType) {
            $resultsGenerator = new ResultsGenerator(
                $this->searchService,
                ['created' => 'asc'],
                100
            );

            $query = 'terms.id:' . $actorType;
            if (!$this->askConfirmation($input, $output, $resultsGenerator->count($query), $actorType, $eventType)) {
                return 0;
            }

            $places = $resultsGenerator->search($query);

            foreach ($places as $place) {
                $placeId = $place->getId();
                try {
                    $this->commandBus->dispatch(new UpdateType($placeId, $eventType));
                    $logger->info(
                        'Successfully changed type of place "' . $placeId . '" to  "' . $eventType . '"'
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
            '8.9.1.0.0' => 'BtVNd33sR0WntjALVbyp3w',
            '8.46.0.0.0' => '0.14.0.0.0',
            '8.9.2.0.0' => 'GnPFp9uvOUyqhOckIFMKmg',
            '8.2.0.0.0' => 'kI7uAyn2uUu9VV6Z3uWZTA',
            '8.47.0.0.0' => 'ekdc4ATGoUitCa0e6me6xA',
            '8.3.0.0.0' => 'GnPFp9uvOUyqhOckIFMKmg',
            '8.4.0.0.0' => 'GnPFp9uvOUyqhOckIFMKmg',
            '8.48.0.0.0' => 'Yf4aZBfsUEu2NsQqsprngw',
            '8.6.0.0.0' => 'Yf4aZBfsUEu2NsQqsprngw',
            '8.5.0.0.0' => 'Yf4aZBfsUEu2NsQqsprngw',
            '8.21.1.0.0' => 'JCjA0i5COUmdjMwcyjNAFA',
            '8.32.0.0.0' => 'YVBc8KVdrU6XfTNvhMYUpg',
            '8.49.0.0.0' => 'ekdc4ATGoUitCa0e6me6xA',
            '8.1.0.0.0' => 'kI7uAyn2uUu9VV6Z3uWZTA',
            '8.44.0.0.0' => 'ekdc4ATGoUitCa0e6me6xA',
            '8.10.0.0.0' => 'rJRFUqmd6EiqTD4c7HS90w',
            '8.50.0.0.0' => 'eBwaUAAhw0ur0Z02i5ttnw',
            '8.51.0.0.0' => '0.53.0.0.0',
            '8.52.0.0.0' => 'OyaPaf64AEmEAYXHeLMAtA',
            '8.53.0.0.0' => '0.8.0.0.0',
            '8.40.0.0.0' => 'VRC6HX0Wa063sq98G5ciqw',
        ];
    }
}
