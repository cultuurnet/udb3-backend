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
        $this->setName('place:actortype:reject');

        $this->setDescription(
            'Rejects all places with a given actorType'
        );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);
        $logger = new ConsoleLogger($output);

        $typesToReject = $this->getTypesToReject();
        /** @var string $typeToReject */
        foreach ($typesToReject as $typeToReject) {
            $resultsGenerator = new ResultsGenerator(
                $this->searchService,
                ['created' => 'asc'],
                100
            );

            $query = 'terms.id:' . $typeToReject;
            if (!$this->askConfirmation($input, $output, $resultsGenerator->count($query), $typeToReject)) {
                return 0;
            }

            $places = $resultsGenerator->search($query);

            foreach ($places as $place) {
                $placeId = $place->getId();
                try {
                    $this->commandBus->dispatch(
                        new Reject(
                            $placeId,
                            new StringLiteral('Place rejected because of a deprecated actorType')
                        )
                    );
                    $logger->info(
                        'Successfully rejected place "' . $placeId . '"'
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

            $logger->info('Successfully rejected  ' . $this->success . ' places');

            if ($this->errors) {
                $logger->error('Failed to reject ' . $this->errors . ' places');
            }
        }
        return $this->errors;
    }

    private function askConfirmation(
        InputInterface $input,
        OutputInterface $output,
        int $count,
        string $typeToReject
    ): bool {
        return $this
            ->getHelper('question')
            ->ask(
                $input,
                $output,
                new ConfirmationQuestion(
                    "This action will reject {$count} places with term {$typeToReject}, continue? [y/N] ",
                    false
                )
            );
    }

    private function getTypesToReject(): array
    {
        return [
            '8.15.0.0.0',
            '8.0.0.0.0',
        ];
    }
}
