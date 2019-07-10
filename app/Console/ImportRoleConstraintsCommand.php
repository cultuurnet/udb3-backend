<?php

namespace CultuurNet\UDB3\Silex\Console;

use CultuurNet\UDB3\Role\Commands\AddConstraint;
use CultuurNet\UDB3\Role\Commands\UpdateConstraint;
use CultuurNet\UDB3\Role\ValueObjects\Query;
use CultuurNet\UDB3\ValueObject\SapiVersion;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ValueObjects\Identity\UUID;

class ImportRoleConstraintsCommand extends AbstractCsvImportCommand
{
    private const ROLE_ID = 'ROLEID';
    private const SAPIVERSION = 'SAPIVERSION';
    private const QUERY = 'QUERY';

    /**
     * @inheritdoc
     * @see Command::configure()
     */
    public function configure()
    {
        parent::configure();
        $this
            ->setName('roles:constraints:import')
            ->setDescription('Imports constraints for a role from a given CSV file')
            ->addOption(
                'mode',
                'm',
                InputOption::VALUE_OPTIONAL,
                'The mode for the import command. Possible values are add or update.',
                'add'
            );
    }

    /**
     * @inheritdoc
     */
    public function getColumnHeaders(): string
    {
        return 'With ' . self::ROLE_ID . ', ' . self::SAPIVERSION . ' and ' . self::QUERY . ' headers.';
    }

    /**
     * @inheritdoc
     */
    public function processRecord(
        InputInterface $input,
        OutputInterface $output,
        array $record
    ): void {
        $output->writeln('Importing constraint for roleID: ' . $record[self::ROLE_ID]);

        $mode = $input->getOption('mode');
        if ('update' === $mode) {
            $this->dispatchUpdateConstraint(
                $record[self::ROLE_ID],
                $record[self::SAPIVERSION],
                $record[self::QUERY]
            );
        } else {
            $this->dispatchAddConstraint(
                $record[self::ROLE_ID],
                $record[self::SAPIVERSION],
                $record[self::QUERY]
            );
        }
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

    /**
     * @param string $roleId
     * @param string $sapiVersion
     * @param string $query
     */
    private function dispatchUpdateConstraint(
        string $roleId,
        string $sapiVersion,
        string $query
    ): void {
        $commandBus = $this->getCommandBus();
        $commandBus->dispatch(
            new UpdateConstraint(
                new UUID($roleId),
                SapiVersion::fromNative($sapiVersion),
                new Query($query)
            )
        );
    }
}
