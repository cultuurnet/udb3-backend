<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security\ResourceOwner\Doctrine;

use CultuurNet\UDB3\Security\ResourceOwner\ResourceOwnerQuery;
use Doctrine\DBAL\Connection;

final class DBALResourceRelatedOwnerRepository implements ResourceOwnerQuery
{
    private Connection $connection;

    private string $idField;

    private string $organizersTable;

    private string $relationsTable;

    public function __construct(
        string $organizersTable,
        string $relationsTable,
        Connection $connection,
        string $idField
    ) {
        $this->organizersTable = $organizersTable;
        $this->relationsTable = $relationsTable;
        $this->connection = $connection;
        $this->idField = $idField;
    }

    public function getEditableResourceIds(string $userId): array
    {
        $q = $this->connection->createQueryBuilder();
        $q->select($this->idField)
            ->from($this->relationsTable, 'r')
            ->innerJoin(
                'r',
                $this->organizersTable,
                'o',
                'r.organizer = o.organizer_id'
            )
            ->where('o.user_id = :userId')
            ->setParameter(':userId', $userId);

        $results = $q->execute();

        $events = [];
        while ($id = $results->fetchColumn(0)) {
            $events[] =$id;
        }

        return $events;
    }
}
