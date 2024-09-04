<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Canonical;

use Doctrine\DBAL\Connection;

class DBALDuplicatePlacesRemovedFromClusterRepository implements DuplicatePlaceRemovedFromClusterRepository
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function addPlace(string $placeId): void
    {
        $this->connection->createQueryBuilder()
            ->insert('duplicate_places_removed_from_cluster')
            ->setValue('place_uuid', ':place_uuid')
            ->setParameter(':place_uuid', $placeId)
            ->execute();
    }

    public function getAllPlaces(): array
    {
        return $this->connection->createQueryBuilder()
            ->select('place_uuid')
            ->from('duplicate_places_removed_from_cluster')
            ->execute()
            ->fetchFirstColumn();
    }

    public function truncateTable(): void
    {
        $this->connection->executeQuery('truncate duplicate_places_removed_from_cluster');
    }
}
