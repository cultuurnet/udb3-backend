<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemIdentifier;
use CultuurNet\UDB3\Place\Commands\Moderation\Reject;
use CultuurNet\UDB3\Search\ResultsGenerator;
use CultuurNet\UDB3\Search\SearchServiceInterface;
use CultuurNet\UDB3\StringLiteral;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;

final class RejectPlaceDeprecatedType extends AbstractCommand
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
        $this->setName('place:actortype:reject');

        $this->setDescription(
            'Rejects all places with a given actorType'
        );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);
        $logger = new ConsoleLogger($output);

        $deprecatedTypeId = $this->askDeprecatedType($input, $output);

        $resultsGenerator = new ResultsGenerator(
            $this->searchService,
            ['created' => 'asc'],
            100
        );

        $query = 'terms.id:' . $deprecatedTypeId;
        if (!$this->askConfirmation($input, $output, $resultsGenerator->count($query))) {
            return 0;
        }

        $places = $resultsGenerator->search($query);

        $success = 0;
        $errors = 0;
        /* @var ItemIdentifier $place */
        foreach ($places as $place) {
            $placeId = $place->getId();
            try {
                $this->commandBus->dispatch(new Reject(
                    new StringLiteral($placeId),
                    new StringLiteral('Place rejected because of a deprecated actorType')
                ));
                $logger->info(
                    'Successfully rejected place "' . $placeId . '"'
                );
                $success++;
            } catch (\Throwable $t) {
                $logger->error(
                    sprintf(
                        'An error occurred while rejecting place "%s": %s with message %s',
                        $placeId,
                        get_class($t),
                        $t->getMessage()
                    )
                );
                $errors++;
            }
        }

        $logger->info('Successfully rejected ' . $success . ' places with id "' . $deprecatedTypeId . '"');

        if ($errors) {
            $logger->error('Failed to reject ' . $errors . ' places');
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

    private function askDeprecatedType(InputInterface $input, OutputInterface $output): string
    {
        $deprecatedTypeIdQuestion = new ChoiceQuestion(
            'Provide deprecated actorType to be updated?',
            [
                '8.15.0.0.0',
                '8.0.0.0.0',
            ]
        );
        $deprecatedTypeIdQuestion->setErrorMessage('Invalid actorType: %s');
        return $this->getHelper('question')->ask($input, $output, $deprecatedTypeIdQuestion);
    }
}
