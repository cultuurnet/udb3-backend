<?php

namespace CultuurNet\UDB3\Storage;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\SqlitePlatform;

/**
 * Class DBALPurgeService
 * @package CultuurNet\UDB3\Storage
 */
class DBALPurgeService implements PurgeServiceInterface
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var string
     */
    private $tableName;

    /**
     * DBALPurgeService constructor.
     * @param Connection $connection
     * @param string $tableName
     */
    public function __construct(
        $connection,
        $tableName
    ) {
        $this->connection = $connection;

        $this->tableName = $tableName;
    }

    public function purgeAll()
    {
        $platform = $this->connection->getDatabasePlatform();
        $sql = $platform->getTruncateTableSQL($this->tableName);
        $this->connection->exec($sql);

        if ($platform instanceof SqlitePlatform) {
            $sql = 'UPDATE SQLITE_SEQUENCE SET SEQ=0 WHERE NAME=' . $this->connection->quoteIdentifier($this->tableName);
            $this->connection->exec($sql);
        }
    }
}
