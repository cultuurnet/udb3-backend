<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;

abstract class AbstractDBALRepository
{
    private Connection $connection;

    private string $tableName;

    public function __construct(
        Connection $connection,
        string $tableName
    ) {
        $this->connection = $connection;
        $this->tableName = $tableName;
    }

    public function getConnection(): Connection
    {
        return $this->connection;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function createQueryBuilder(): QueryBuilder
    {
        return $this->connection->createQueryBuilder();
    }
}
