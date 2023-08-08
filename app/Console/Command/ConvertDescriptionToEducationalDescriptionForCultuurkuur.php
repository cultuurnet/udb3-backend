<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Organizer\Commands\ConvertDescriptionToEducationalDescription;
use CultuurNet\UDB3\Search\OrganizersSapi3SearchService;
use CultuurNet\UDB3\Search\ResultsGenerator;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConvertDescriptionToEducationalDescriptionForCultuurkuur extends AbstractCommand
{
    private const QUERY_LABEL = 'labels:cultuurkuur';
    private const BATCH_SIZE = 100;

    private OrganizersSapi3SearchService $searchService;

    public function __construct(
        CommandBus $commandBus,
        OrganizersSapi3SearchService $searchService
    ) {
        parent::__construct($commandBus);

        $this->searchService = $searchService;
    }

    protected function configure(): void
    {
        $this
            ->setName('organizer:cultuurkuur:convert-educational-description')
            ->setDescription('Take the description of the cultuurkuur organizers and move it to educational description');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $generator = $this->getResultsGenerator();

        $organisations = $generator->search(self::QUERY_LABEL);
        $count = $generator->count(self::QUERY_LABEL);

        $output->writeln(sprintf('Found %d organisations with the label Cultuurkuur', $count));
        $progressBar = new ProgressBar($output, $count);

        foreach ($organisations as $organizerId => $itemIdentifier) {
            $this->commandBus->dispatch(new ConvertDescriptionToEducationalDescription(
                $organizerId
            ));

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
}
