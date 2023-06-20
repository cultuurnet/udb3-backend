<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use CultuurNet\UDB3\StringLiteral;

abstract class AbstractDBALRepository
{
    private Connection $connection;

    private StringLiteral $tableName;

    public function __construct(
        Connection $connection,
        StringLiteral $tableName
    ) {
        $this->connection = $connection;
        $this->tableName = $tableName;
    }

    public function getConnection(): Connection
    {
        return $this->connection;
    }

    public function getTableName(): StringLiteral
    {
        return $this->tableName;
    }

    public function createQueryBuilder(): QueryBuilder
    {
        return $this->connection->createQueryBuilder();
    }
}
