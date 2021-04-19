<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Console;

use Doctrine\DBAL\Connection;
use Knp\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PurgeModelCommand extends Command
{
    /**
     * @var string[]
     */
    private $tablesToPurge;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @param string[] $tablesToPurge
     */
    public function __construct(array $tablesToPurge, Connection $connection)
    {
        parent::__construct();
        $this->tablesToPurge = $tablesToPurge;
        $this->connection = $connection;
    }

    protected function configure()
    {
        $this->setName('purge')->setDescription('Purge all read models');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->tablesToPurge as $tableToPurge) {
            $platform = $this->connection->getDatabasePlatform();
            $sql = $platform->getTruncateTableSQL($tableToPurge);
            $this->connection->exec($sql);
        }

        return 0;
    }
}
