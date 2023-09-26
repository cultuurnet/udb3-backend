<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security\ResourceOwner\Doctrine;

use CultuurNet\UDB3\Security\ResourceOwner\ResourceOwnerQuery;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use CultuurNet\UDB3\Security\ResourceOwner\ResourceOwnerRepository;

final class DBALResourceOwnerRepository implements ResourceOwnerRepository, ResourceOwnerQuery
{
    private Connection $connection;

    private string $idField;

    private string $tableName;

    public function __construct(
        string $tableName,
        Connection $connection,
        string $idField
    ) {
        $this->tableName = $tableName;
        $this->connection = $connection;
        $this->idField = $idField;
    }

    public function getEditableResourceIds(string $userId): array
    {
        $q = $this->connection->createQueryBuilder();
        $q->select($this->idField)
            ->from($this->tableName)
            ->where('user_id = :userId')
            ->setParameter(':userId', $userId);

        $results = $q->execute();

        $events = [];
        while ($id = $results->fetchColumn(0)) {
            $events[] =$id;
        }

        return $events;
    }

    public function markResourceEditableByUser(string $resourceId, string $userId): void
    {
        try {
            $this->connection->insert(
                $this->tableName,
                [
                    $this->idField => $resourceId,
                    'user_id' => $userId,
                ]
            );
        } catch (UniqueConstraintViolationException $e) {
            // Intentionally catching database exception occurring when the
            // permission record is already in place.
        }
    }

    public function markResourceEditableByNewUser(string $resourceId, string $userId): void
    {
        if ($this->getResourceCount($resourceId) === 0) {
            $this->connection->insert(
                $this->tableName,
                [
                    $this->idField => $resourceId,
                    'user_id' => $userId,
                ]
            );
        } else {
            $this->connection->update(
                $this->tableName,
                ['user_id' => $userId],
                [$this->idField => $resourceId]
            );
        }
    }

    private function getResourceCount(string $resourceId): int
    {
        return count($this->connection->createQueryBuilder()
            ->select($this->idField)
            ->from($this->tableName)
            ->where($this->idField . ' = :resource_id')
            ->setParameter(':resource_id', $resourceId)
            ->execute()
            ->fetchAll());
    }
}
