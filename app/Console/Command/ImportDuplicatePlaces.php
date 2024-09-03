<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use CultuurNet\UDB3\Place\Canonical\DBALDuplicatePlaceRepository;
use CultuurNet\UDB3\Place\DuplicatePlace\ImportDuplicatePlacesProcessor;
use Psr\Log\LoggerInterface;
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
    private LoggerInterface $logger;

    public function __construct(
        DBALDuplicatePlaceRepository $dbalDuplicatePlaceRepository,
        ImportDuplicatePlacesProcessor $importDuplicatePlacesProcessor,
        LoggerInterface $logger
    ) {
        parent::__construct();

        $this->importDuplicatePlacesProcessor = $importDuplicatePlacesProcessor;
        $this->dbalDuplicatePlaceRepository = $dbalDuplicatePlaceRepository;
        $this->logger = $logger;
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
        $placesToImport = $this->dbalDuplicatePlaceRepository->howManyPlacesAreToBeImported();
        $clusterChangeResult = $this->dbalDuplicatePlaceRepository->calculateHowManyClustersHaveChanged();

        if ($placesToImport === 0) {
            $msg = 'Import duplicate places failed. Duplicate_places_import table is empty.';
            $this->logger->error($msg);
            $output->writeln(sprintf('<error>%s</error>', $msg));
            return self::FAILURE;
        }

        if ($clusterChangeResult->getAmountRemovedClusters() === 0 && $clusterChangeResult->getAmountNewClusters() === 0) {
            $output->writeln('duplicate_places is already synced');
            return self::SUCCESS;
        }

        if (!$this->askConfirmation(
            $input,
            $output,
            sprintf(
                'This action will change a total of %d new places, and remove %d places from the duplicate places table. Do you want to continue? [y/N] ',
                $clusterChangeResult->getAmountNewClusters(),
                $clusterChangeResult->getAmountRemovedClusters(),
            )
        )) {
            return self::SUCCESS;
        }

        // Everything before this was just safety checks, below is the actual code that syncs duplicate places
        $this->importDuplicatePlacesProcessor->sync();

        $output->writeln('Duplicate places are synced and old clusters where removed. You probably want to run place:process-duplicates to give canonicals to the new clusters now.');

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
