<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use CultuurNet\UDB3\Kinepolis\MovieMappingRepository;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use League\Csv\Reader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ImportMovieIdsFromCsv extends Command
{
    private const CSV_FILE = 'csv_file';
    private MovieMappingRepository $mappingRepository;

    private DocumentRepository $eventDocumentRepository;

    public function __construct(
        MovieMappingRepository $mappingRepository,
        DocumentRepository $eventDocumentRepository
    )
    {
        parent::__construct();
        $this->mappingRepository = $mappingRepository;
        $this->eventDocumentRepository = $eventDocumentRepository;
    }

    protected function configure(): void
    {
        $this->setName('movies:migrate')
            ->setDescription('Migrates the movieIds from the legacy application to UDB3 via a CSV')
            ->addArgument(
                self::CSV_FILE,
                InputArgument::REQUIRED,
                'Full path to the csv file to import.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $csvReader = Reader::createFromPath(
            $input->getArgument(self::CSV_FILE)
        );

        $records = $csvReader->getRecords();
        $output->writeln('Starting import.');
        foreach ($records as $record) {
            try {
                $this->eventDocumentRepository->fetch($record[0]);
            } catch (DocumentDoesNotExist $documentDoesNotExist){
                $output->writeln('Did not found event with id: ' . $record[0]);
                return 1;
            }
            $this->mappingRepository->create($record[0], $record[1]);
        }

        $output->writeln('Finished import.');

        return 0;
    }
}
