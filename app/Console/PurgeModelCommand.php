<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Console;

use Doctrine\DBAL\Connection;
use Knp\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PurgeModelCommand extends Command
{
    private const TABLES_TO_PURGE = [
        'event_permission_readmodel',
        'event_relations',
        'labels_json',
        'label_roles',
        'labels_relations',
        'organizer_permission_readmodel',
        'place_permission_readmodel',
        'place_relations',
        'role_permissions',
        'roles_search_v3',
        'user_roles',
        'offer_metadata',
    ];

    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        parent::__construct();
        $this->connection = $connection;
    }

    protected function configure()
    {
        $this->setName('purge')->setDescription('Purge all read models');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach (self::TABLES_TO_PURGE as $tableToPurge) {
            $platform = $this->connection->getDatabasePlatform();
            $sql = $platform->getTruncateTableSQL($tableToPurge);
            $this->connection->exec($sql);
        }

        return 0;
    }
}
