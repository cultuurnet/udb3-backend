<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Storage;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\SqlitePlatform;

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

    public function __construct(
        Connection $connection,
        string $tableName
    ) {
        $this->connection = $connection;
        $this->tableName = $tableName;
    }

    public function purgeAll(): void
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
