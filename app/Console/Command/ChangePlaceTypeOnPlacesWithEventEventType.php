<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemIdentifier;
use CultuurNet\UDB3\Offer\Commands\UpdateType;
use CultuurNet\UDB3\Place\Commands\Moderation\Reject;
use CultuurNet\UDB3\Search\ResultsGenerator;
use CultuurNet\UDB3\Search\SearchServiceInterface;
use CultuurNet\UDB3\StringLiteral;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;

final class ChangePlaceTypeOnPlacesWithEventEventType extends AbstractCommand
{
    private SearchServiceInterface $searchService;

    public function __construct(
        CommandBus $commandBus,
        SearchServiceInterface $searchService
    ) {
        $this->searchService = $searchService;
        parent::__construct($commandBus);
    }

    public function configure(): void
    {
        $this->setName('place:eventtype:update:reject');

        $this->setDescription(
            'Updates all places with an Event eventType and optionally reject them'
        );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);
        $logger = new ConsoleLogger($output);

        $eventEventType = $this->askEventEventType($input, $output);
        $placeEventType = $this->askPlaceEventType($input, $output);

        $resultsGenerator = new ResultsGenerator(
            $this->searchService,
            ['created' => 'asc'],
            100
        );

        $query = 'terms.id:' . $eventEventType;
        if (!$this->askConfirmation($input, $output, $resultsGenerator->count($query))) {
            return 0;
        }

        $reject = $this->askToReject($input, $output);

        $places = $resultsGenerator->search($query);

        $success = 0;
        $errors = 0;
        /* @var ItemIdentifier $place */
        foreach ($places as $place) {
            $placeId = $place->getId();
            try {
                $this->commandBus->dispatch(new UpdateType($placeId, $placeEventType));
                if ($reject) {
                    $this->commandBus->dispatch(
                        new Reject(
                            new StringLiteral($placeId),
                            new StringLiteral('Place rejected because it used an eventType only valid for events')
                        )
                    );
                }
                $logger->info(
                    'Successfully changed type of place "' . $placeId . '" to  "' . $placeEventType . '"'
                );
                $success++;
            } catch (\Throwable $t) {
                $logger->error(
                    sprintf(
                        'An error occurred while changing type of place "%s": %s with message %s',
                        $placeId,
                        get_class($t),
                        $t->getMessage()
                    )
                );
                $errors++;
            }
        }

        $logger->info('Successfully changed ' . $success . ' places to type with id "' . $placeEventType . '"');

        if ($errors) {
            $logger->error('Failed to change type of ' . $errors . ' places');
        }

        return $errors;
    }

    private function askConfirmation(InputInterface $input, OutputInterface $output, int $count): bool
    {
        return $this
            ->getHelper('question')
            ->ask(
                $input,
                $output,
                new ConfirmationQuestion(
                    "This action will process {$count} places, continue? [y/N] ",
                    false
                )
            );
    }

    private function askToReject(InputInterface $input, OutputInterface $output): bool
    {
        return $this
            ->getHelper('question')
            ->ask(
                $input,
                $output,
                new ConfirmationQuestion(
                    "Should these places get the WorkflowStatus rejected? [y/N] ",
                    false
                )
            );
    }

    private function askEventEventType(InputInterface $input, OutputInterface $output): string
    {
        $eventTypeIdQuestion = new ChoiceQuestion(
            'Provide Event eventType to be updated?',
            [
                '0.50.4.0.0',
                '0.54.0.0.0',
                '0.5.0.0.0',
                '0.50.6.0.0',
                '0.3.2.0.0',
                '0.55.0.0.0',
                '0.19.0.0.0',
                '0.37.0.0.0',
                '0.12.0.0.0',
                '0.6.0.0.0',
                '0.0.0.0.0',
                '0.7.0.0.0',
                '0.3.1.0.0',
                '0.3.1.0.1',
                '0.57.0.0.0',
                '0.28.0.0.0',
                '0.17.0.0.0',
                '0.49.0.0.0',
                '1.50.0.0.0',
                '0.50.21.0.0',
                '0.59.0.0.0',
                '0.100.0.0.0',
                '0.100.1.0.0',
                '0.100.2.0.0',
                '0.51.0.0.0',
            ]
        );
        $eventTypeIdQuestion->setErrorMessage('Invalid eventType: %s');
        return $this->getHelper('question')->ask($input, $output, $eventTypeIdQuestion);
    }

    private function askPlaceEventType(InputInterface $input, OutputInterface $output): string
    {
        $newTypeIdQuestion =  new ChoiceQuestion(
            'Provide new eventType to replace eventType?',
            [
                'OyaPaf64AEmEAYXHeLMAtA',
                '8.70.0.0.0',
                '0.53.0.0.0',
                'BtVNd33sR0WntjALVbyp3w',
                'Yf4aZBfsUEu2NsQqsprngw',
                '8.70.0.0.0',
                'eBwaUAAhw0ur0Z02i5ttnw',
                '0.8.0.0.0',
                '0.53.0.0.0',
                'OyaPaf64AEmEAYXHeLMAtA',
                'GnPFp9uvOUyqhOckIFMKmg',
                'GnPFp9uvOUyqhOckIFMKmg',
                'rJRFUqmd6EiqTD4c7HS90w',
                'rJRFUqmd6EiqTD4c7HS90w',
                'JCjA0i5COUmdjMwcyjNAFA',
                'OyaPaf64AEmEAYXHeLMAtA',
                '0.8.0.0.0',
                'YVBc8KVdrU6XfTNvhMYUpg',
                'OyaPaf64AEmEAYXHeLMAtA',
                'Yf4aZBfsUEu2NsQqsprngw',
                'eBwaUAAhw0ur0Z02i5ttnw',
                'Yf4aZBfsUEu2NsQqsprngw',
                '0.53.0.0.0',
                '0.53.0.0.0',
                'Yf4aZBfsUEu2NsQqsprngw',
            ]
        );
        $newTypeIdQuestion->setErrorMessage('Invalid eventType: %s');
        return $this->getHelper('question')->ask($input, $output, $newTypeIdQuestion);
    }
}
