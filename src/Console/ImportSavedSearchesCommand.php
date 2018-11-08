<?php

namespace CultuurNet\UDB3\Silex\Console;

use CultuurNet\UDB3\SavedSearches\Properties\QueryString;
use CultuurNet\UDB3\SavedSearches\UDB3SavedSearchRepository;
use CultuurNet\UDB3\ValueObject\SapiVersion;
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
    private const SAPI_VERSION = 'sapi_version';

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
                self::SAPI_VERSION,
                's',
                InputOption::VALUE_OPTIONAL,
                'The sapi version of the queries (v2 or v3). The default is v2',
                'v2'
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

            $this->getUDB3SavedSearchesRepository($input)->write(
                new StringLiteral($record['USER_UUID']),
                new StringLiteral($record['NAME']),
                $this->getQueryString($record)
            );
        }

        $output->writeln('Finished import.');
    }

    /**
     * @return UDB3SavedSearchRepository
     */
    private function getUDB3SavedSearchesRepository(InputInterface $input)
    {
        $app = $this->getSilexApplication();

        if ($input->getOption(self::SAPI_VERSION) === SapiVersion::V3) {
            return $app['udb3_saved_searches_repo_sapi3'];
        } else {
            return $app['udb3_saved_searches_repo_sapi2'];
        }
    }

    /**
     * @param array $record
     * @return QueryString
     */
    private function getQueryString(array $record): QueryString
    {
        if (substr($record['QUERY'], 0, 2) === 'q=') {
            return new QueryString(substr($record['QUERY'], 2));
        } else {
            return new QueryString($record['QUERY']);
        }
    }
}
