<?php

namespace CultuurNet\UDB3\Silex\Console;

use CultuurNet\UDB3\Role\Commands\AddConstraint;
use CultuurNet\UDB3\Role\ValueObjects\Query;
use CultuurNet\UDB3\ValueObject\SapiVersion;
use League\Csv\Reader;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ValueObjects\Identity\UUID;

class ImportRoleConstraintsCommand extends AbstractCommand
{
    private const ROLE_ID = 'ROLEID';
    private const SAPIVERSION = 'SAPIVERSION';
    private const QUERY = 'QUERY';
    private const CSV_FILE_ARG = 'csv_file';
    private const CSV_DELIMETER_OPT = 'csv_delimiter';

    /**
     * @inheritdoc
     * @see Command::configure()
     */
    public function configure()
    {
        $this
            ->setName('roles:constraints:import')
            ->setDescription('Import constraints for a role from the given CSV file')
            ->addArgument(
                self::CSV_FILE_ARG,
                InputArgument::REQUIRED,
                'Full path to the csv file to import. With ' . self::ROLE_ID . ', ' . self::SAPIVERSION . ' and ' . self::QUERY . ' headers.'
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
     * @see Command::execute()
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
            $output->writeln('Importing constraint for roleID: ' . $record[self::ROLE_ID]);

            $this->dispatchAddConstraint($record[self::ROLE_ID], $record[self::SAPIVERSION], $record[self::QUERY]);
        }

        $output->writeln('Finished import.');
    }

    /**
     * @param string $roleId
     * @param string $sapiVersion
     * @param string $query
     */
    private function dispatchAddConstraint(
        string $roleId,
        string $sapiVersion,
        string $query
    ): void {
        $commandBus = $this->getCommandBus();
        $commandBus->dispatch(
            new AddConstraint(
                new UUID($roleId),
                SapiVersion::fromNative($sapiVersion),
                new Query($query)
            )
        );
    }
}
