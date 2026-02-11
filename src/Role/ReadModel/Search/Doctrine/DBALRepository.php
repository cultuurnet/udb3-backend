<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ReadModel\Search\Doctrine;

use CultuurNet\UDB3\Role\ReadModel\Search\RepositoryInterface;
use CultuurNet\UDB3\Role\ReadModel\Search\Results;
use Doctrine\DBAL\Connection;

class DBALRepository implements RepositoryInterface
{
    protected Connection $connection;

    protected string $tableName;

    public function __construct(Connection $connection, string $tableName)
    {
        $this->connection = $connection;
        $this->tableName = $tableName;
    }

    public function remove(string $uuid): void
    {
        $q = $this->connection->createQueryBuilder();
        $expr = $this->connection->getExpressionBuilder();

        $q
            ->delete($this->tableName)
            ->where($expr->eq(ColumnNames::UUID_COLUMN, ':role_id'))
            ->setParameter('role_id', $uuid);
        $q->execute();
    }

    public function save(string $uuid, string $name, string $constraint = null): void
    {
        // Use INSERT ... ON DUPLICATE KEY UPDATE to handle replay scenarios where the role already exists in the projection table
        $sql = sprintf(
            'INSERT INTO %s (%s, %s, %s) VALUES (:role_id, :role_name, :constraint)
             ON DUPLICATE KEY UPDATE %s = :role_name',
            $this->tableName,
            ColumnNames::UUID_COLUMN,
            ColumnNames::NAME_COLUMN,
            ColumnNames::CONSTRAINT_COLUMN,
            ColumnNames::NAME_COLUMN
        );

        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue('role_id', $uuid);
        $stmt->bindValue('role_name', $name);
        $stmt->bindValue('constraint', $constraint);
        $stmt->execute();
    }

    public function search(string $query = '', int $limit = 10, int $start = 0): Results
    {
        $q = $this->connection->createQueryBuilder();
        $expr = $this->connection->getExpressionBuilder();

        // Results.
        $q
            ->select('uuid', 'name')
            ->from($this->tableName)
            ->orderBy('name', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult($start);

        if (!empty($query)) {
            $q->where($expr->like('name', ':role_name'));
            $q->setParameter('role_name', '%' . $query . '%');
        }

        $results = $q->execute()->fetchAllAssociative();

        //Total.
        $q = $this->connection->createQueryBuilder();

        $q
            ->resetQueryParts()
            ->select('COUNT(*) AS total')
            ->from($this->tableName);

        if (!empty($query)) {
            $q->where($expr->like('name', ':role_name'));
            $q->setParameter('role_name', '%' . $query . '%');
        }

        $total = $q->execute()->fetchColumn();

        return new Results($limit, $results, (int) $total);
    }

    public function updateName(string $uuid, string $name): void
    {
        $q = $this->connection->createQueryBuilder();
        $expr = $this->connection->getExpressionBuilder();

        $q
            ->update($this->tableName)
            ->where($expr->eq(ColumnNames::UUID_COLUMN, ':role_id'))
            ->set(ColumnNames::UUID_COLUMN, ':role_id')
            ->set(ColumnNames::NAME_COLUMN, ':role_name')
            ->setParameter('role_id', $uuid)
            ->setParameter('role_name', $name);
        $q->execute();
    }

    public function updateConstraint(string $uuid, string $constraint = null): void
    {
        $q = $this->connection->createQueryBuilder();
        $expr = $this->connection->getExpressionBuilder();

        $q
            ->update($this->tableName)
            ->where($expr->eq(ColumnNames::UUID_COLUMN, ':role_id'))
            ->set(ColumnNames::UUID_COLUMN, ':role_id')
            ->set(ColumnNames::CONSTRAINT_COLUMN, ':constraint')
            ->setParameter('role_id', $uuid)
            ->setParameter('constraint', $constraint);
        $q->execute();
    }
}
