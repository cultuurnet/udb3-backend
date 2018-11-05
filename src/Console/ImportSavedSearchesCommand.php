<?php

namespace CultuurNet\UDB3\Silex\Console;

use CultuurNet\UDB3\SavedSearches\Properties\QueryString;
use CultuurNet\UDB3\SavedSearches\UDB3SavedSearchRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ValueObjects\StringLiteral\StringLiteral;

class ImportSavedSearchesCommand extends AbstractCsvImportCommand
{
    private const NAME = 'NAME';
    private const QUERY = 'QUERY';
    private const USERID = 'USER_UUID';

    /**
     * @inheritdoc
     */
    public function configure()
    {
        parent::configure();
        $this
            ->setName('savedSearches:import')
            ->setDescription('Import saved searches from the given CSV file');
    }

    /**
     * @inheritdoc
     */
    public function getColumnHeaders(): string
    {
        return 'With ' . self::NAME . ', ' . self::QUERY . ' and ' . self::USERID . ' headers.';
    }

    /**
     * @inheritdoc
     */
    public function processRecord(
        InputInterface $input,
        OutputInterface $output,
        array $record
    ): void {
        $output->writeln('Importing query with name: ' . $record[self::NAME]);

        $this->getUDB3SavedSearchesRepository()->write(
            new StringLiteral($record[self::USERID]),
            new StringLiteral($record[self::NAME]),
            $this->getQueryString($record)
        );
    }

    /**
     * @return UDB3SavedSearchRepository
     */
    private function getUDB3SavedSearchesRepository()
    {
        $app = $this->getSilexApplication();
        return $app['udb3_saved_searches_repo_sapi2'];
    }

    /**
     * @param array $record
     * @return QueryString
     */
    private function getQueryString(array $record): QueryString
    {
        if (substr($record[self::QUERY], 0, 2) === 'q=') {
            return new QueryString(substr($record[self::QUERY], 2));
        } else {
            return new QueryString($record[self::QUERY]);
        }
    }
}
