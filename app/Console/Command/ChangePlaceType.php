<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemIdentifier;
use CultuurNet\UDB3\Offer\Commands\UpdateType;
use CultuurNet\UDB3\Search\ResultsGenerator;
use CultuurNet\UDB3\Search\SearchServiceInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

final class ChangePlaceType extends AbstractCommand
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
        $this->setName('place:actortype:update');

        $this->setDescription(
            'Updates all places with an actor type with their corresponding event type'
        );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);
        $logger = new ConsoleLogger($output);

        $deprecatedTypeId = $this->askDeprecatedType($input, $output);
        $newTypeId = $this->askNewTypeId($input, $output);

        $resultsGenerator = new ResultsGenerator(
            $this->searchService,
            ['created' => 'asc'],
            100
        );

        $query = 'terms.id:' . $deprecatedTypeId;
        $places = $resultsGenerator->search($query);

        $success = 0;
        $errors = 0;
        /* @var ItemIdentifier $place */
        foreach ($places as $place) {
            $placeId = $place->getId();
            try {
                $this->commandBus->dispatch(new UpdateType($placeId, $newTypeId));
                $logger->info(
                    'Successfully changed type of place "' . $placeId . '" to  "' . $newTypeId . '"'
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

        $logger->info('Successfully changed ' . $success . ' places to type with id "' . $newTypeId . '"');

        if ($errors) {
            $logger->error('Failed to change type of ' . $errors . ' places');
        }

        return $errors;
    }

    private function askDeprecatedType(InputInterface $input, OutputInterface $output): string
    {
        $deprecatedTypeIdQuestion = new ChoiceQuestion(
            'Provide deprecated actorType to be updated?',
            [
                '8.9.1.0.0',
                '8.46.0.0.0',
                '8.9.2.0.0',
                '8.2.0.0.0',
                '8.47.0.0.0',
                '8.3.0.0.0',
                '8.4.0.0.0',
                '8.48.0.0.0',
                '8.6.0.0.0',
                '8.5.0.0.0',
                '8.21.1.0.0',
                '8.32.0.0.0',
                '8.49.0.0.0',
                '8.1.0.0.0',
                '8.44.0.0.0',
                '8.10.0.0.0',
                '8.50.0.0.0',
                '8.51.0.0.0',
                '8.52.0.0.0',
                '8.53.0.0.0',
                '8.40.0.0.0',
            ]
        );
        $deprecatedTypeIdQuestion->setErrorMessage('Invalid actorType: %s');
        return $this->getHelper('question')->ask($input, $output, $deprecatedTypeIdQuestion);
    }

    private function askNewTypeId(InputInterface $input, OutputInterface $output): string
    {
        $newTypeIdQuestion =  new ChoiceQuestion(
            'Provide new eventType to replace actorType?',
            [
                '0.14.0.0.0',
                '0.15.0.0.0',
                '3CuHvenJ+EGkcvhXLg9Ykg',
                'GnPFp9uvOUyqhOckIFMKmg',
                'kI7uAyn2uUu9VV6Z3uWZTA',
                '0.53.0.0.0',
                '0.41.0.0.0',
                'rJRFUqmd6EiqTD4c7HS90w',
                'eBwaUAAhw0ur0Z02i5ttnw',
                'VRC6HX0Wa063sq98G5ciqw',
                'JCjA0i5COUmdjMwcyjNAFA',
                'Yf4aZBfsUEu2NsQqsprngw',
                'YVBc8KVdrU6XfTNvhMYUpg',
                'BtVNd33sR0WntjALVbyp3w',
                'ekdc4ATGoUitCa0e6me6xA',
                'OyaPaf64AEmEAYXHeLMAtA',
                '0.8.0.0.0',
                '8.70.0.0.0',
                'wwjRVmExI0w6xfQwT1KWpx',
            ]
        );
        $newTypeIdQuestion->setErrorMessage('Invalid eventType: %s');
        return $this->getHelper('question')->ask($input, $output, $newTypeIdQuestion);
    }
}
