<?php

namespace CultuurNet\UDB3\Label\ReadModels\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use ValueObjects\StringLiteral\StringLiteral;

abstract class AbstractDBALRepository
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var StringLiteral
     */
    private $tableName;

    public function __construct(
        Connection $connection,
        StringLiteral $tableName
    ) {
        $this->connection = $connection;
        $this->tableName = $tableName;
    }

    /**
     * @return Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @return StringLiteral
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * @return QueryBuilder
     */
    public function createQueryBuilder()
    {
        return $this->connection->createQueryBuilder();
    }
}
