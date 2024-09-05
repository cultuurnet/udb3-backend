<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use CultuurNet\UDB3\Place\Canonical\DBALDuplicatePlaceRepository;
use CultuurNet\UDB3\Place\Canonical\ImportDuplicatePlacesProcessor;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class ImportDuplicatePlaces extends BaseCommand
{
    private const FORCE = 'force';

    private ImportDuplicatePlacesProcessor $importDuplicatePlacesProcessor;
    private DBALDuplicatePlaceRepository $dbalDuplicatePlaceRepository;

    public function __construct(
        DBALDuplicatePlaceRepository $dbalDuplicatePlaceRepository,
        ImportDuplicatePlacesProcessor $importDuplicatePlacesProcessor
    ) {
        parent::__construct();

        $this->importDuplicatePlacesProcessor = $importDuplicatePlacesProcessor;
        $this->dbalDuplicatePlaceRepository = $dbalDuplicatePlaceRepository;
    }

    public function configure(): void
    {
        $this
            ->setName('place:duplicate-places:import')
            ->setDescription('Import duplicate places from the import tables, set clusters ready for processing')
            ->addOption(
                self::FORCE,
                null,
                InputOption::VALUE_NONE,
                'Skip confirmation.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $howManyPlacesAreToBeImported = $this->dbalDuplicatePlaceRepository->howManyPlacesAreToBeImported();
        $howManyPlacesAreToBeDeleted = count($this->dbalDuplicatePlaceRepository->getPlacesNoLongerInCluster());

        if ($howManyPlacesAreToBeImported === 0 && $howManyPlacesAreToBeDeleted === 0) {
            $output->writeln('duplicate_places is already synced');
            return self::SUCCESS;
        }

        if (!$this->askConfirmation(
            $input,
            $output,
            sprintf(
                'This action will sync a total of %d new places, and remove %d places from the duplicate places table. Do you want to continue? [y/N] ',
                $howManyPlacesAreToBeImported,
                $howManyPlacesAreToBeDeleted,
            )
        )) {
            return self::SUCCESS;
        }

        $this->importDuplicatePlacesProcessor->sync();

        $output->writeln('Duplicate places were synced and old clusters were removed. You probably want to run place:process-duplicates to give canonicals to the new clusters now.');

        return self::SUCCESS;
    }

    private function askConfirmation(InputInterface $input, OutputInterface $output, string $message): bool
    {
        if ($input->getOption(self::FORCE)) {
            return true;
        }

        return $this
            ->getHelper('question')
            ->ask(
                $input,
                $output,
                new ConfirmationQuestion(
                    $message,
                    true
                )
            );
    }
}
