<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Organizer\Commands\UpdateEducationalDescription;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\Search\OrganizersSapi3SearchService;
use CultuurNet\UDB3\Search\ResultsGenerator;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class ConvertDescriptionToEducationalDescriptionForCultuurkuur extends AbstractCommand
{
    private const OPTION_FORCED = 'force';

    private const QUERY_LABEL = 'labels:cultuurkuur_organizer';
    private const BATCH_SIZE = 100;

    private OrganizersSapi3SearchService $searchService;
    private DocumentRepository $repository;

    public function __construct(
        CommandBus $commandBus,
        OrganizersSapi3SearchService $searchService,
        DocumentRepository $repository
    ) {
        parent::__construct($commandBus);

        $this->searchService = $searchService;
        $this->commandBus = $commandBus;
        $this->repository = $repository;
    }

    protected function configure(): void
    {
        $this
            ->setName('organizer:convert-educational-description')
            ->setDescription('Take the description of the cultuurkuur organizers and move it to educational description')
            ->addOption(
                self::OPTION_FORCED,
                null,
                InputOption::VALUE_NONE,
                'Do not ask for confirmation.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $generator = $this->getResultsGenerator();

        $organisations = $generator->search(self::QUERY_LABEL);
        $count = $generator->count(self::QUERY_LABEL);

        $output->writeln(sprintf('Found %d organisations with the label Cultuurkuur', $count));

        if (!$this->askConfirmation($input, $output)) {
            return 1;
        }

        $progressBar = new ProgressBar($output, $count);

        foreach ($organisations as $organizerId => $itemIdentifier) {
            try {
                $organisation = $this->repository->fetch($organizerId)->getBody();

                if (!property_exists($organisation, 'description')) {
                    continue;
                }

                foreach ($organisation->description as $lang => $description) {
                    $this->commandBus->dispatch(new UpdateEducationalDescription(
                        $organizerId,
                        new Description($description),
                        new Language($lang),
                    ));
                }
            } catch (DocumentDoesNotExist $e) {
                $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
            }

            $progressBar->advance();
        }

        $progressBar->finish();

        return 1;
    }

    private function getResultsGenerator(): ResultsGenerator
    {
        return new ResultsGenerator(
            $this->searchService,
            ['created' => 'asc'],
            self::BATCH_SIZE
        );
    }

    private function askConfirmation(InputInterface $input, OutputInterface $output): bool
    {
        if ($input->getOption(self::OPTION_FORCED)) {
            return true;
        }

        return $this
            ->getHelper('question')
            ->ask(
                $input,
                $output,
                new ConfirmationQuestion('Are you sure you want to continue? (Yes/No) ', false)
            );
    }
}
