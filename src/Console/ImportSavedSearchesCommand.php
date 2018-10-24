<?php

namespace CultuurNet\UDB3\Silex\Console;

use CultuurNet\UDB3\SavedSearches\Properties\QueryString;
use CultuurNet\UDB3\SavedSearches\UDB3SavedSearchRepository;
use League\Csv\Reader;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ValueObjects\StringLiteral\StringLiteral;

class ImportSavedSearchesCommand extends AbstractCommand
{
    private const CSV_FILE_ARG = 'csv_file';
    private const CSV_DELIMETER_OPT = 'csv_delimiter';

    /**
     * @inheritdoc
     */
    public function configure()
    {
        $this
            ->setName('savedSearches:import')
            ->setDescription('Import saved searches from the given CSV file')
            ->addArgument(
                self::CSV_FILE_ARG,
                InputArgument::REQUIRED,
                'Full path to the csv file to import. With NAME, QUERY and USER_ID headers.'
            )
            ->addOption(
                self::CSV_DELIMETER_OPT,
                'd',
                InputOption::VALUE_OPTIONAL,
                'Delimeter for the csv file (default is comma).',
                ','
            );
    }

    /**
     * @inheritdoc
     * @throws \League\Csv\Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $csvReader = Reader::createFromPath(
            $input->getArgument(self::CSV_FILE_ARG)
        );

        $csvReader->setHeaderOffset(0)
            ->setDelimiter(
                $input->getOption(self::CSV_DELIMETER_OPT)
            );

        $output->writeln('Starting import...');

        $records = $csvReader->getRecords();
        foreach ($records as $record) {
            $output->writeln('Importing query with name: ' . $record['NAME']);

            $this->getUDB3SavedSearchesRepository()->write(
                new StringLiteral($record['USER_UUID']),
                new StringLiteral($record['NAME']),
                new QueryString($record['QUERY'])
            );
        }

        $output->writeln('Finished import.');
    }

    /**
     * @return UDB3SavedSearchRepository
     */
    private function getUDB3SavedSearchesRepository()
    {
        $app = $this->getSilexApplication();
        return $app['udb3_saved_searches_repo'];
    }
}
