<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Canonical;

use Doctrine\DBAL\Connection;

class DBALDuplicatePlaceRepository implements DuplicatePlaceRepository
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getClusterIds(): array
    {
        return $this->connection->createQueryBuilder()
            ->select('DISTINCT cluster_id')
            ->from('duplicate_places')
            ->orderBy('cluster_id')
            ->execute()
            ->fetchFirstColumn();
    }

    public function getPlacesInCluster(string $clusterId): array
    {
        return $this->connection->createQueryBuilder()
            ->select('place_uuid')
            ->from('duplicate_places')
            ->where('cluster_id = :cluster_id')
            ->setParameter(':cluster_id', $clusterId)
            ->execute()
            ->fetchFirstColumn();
    }

    public function setCanonicalOnCluster(string $clusterId, string $canonical): void
    {
        $this->connection->createQueryBuilder()
            ->update('duplicate_places')
            ->set('canonical', ':canonical')
            ->where('cluster_id = :cluster_id')
            ->andWhere('place_uuid != :canonical')
            ->setParameters([
                ':canonical' => $canonical,
                ':cluster_id' => $clusterId,
            ])
            ->execute();
    }

    public function getCanonicalOfPlace(string $placeId): ?string
    {
        $rows = $this->connection->createQueryBuilder()
            ->select('canonical')
            ->from('duplicate_places')
            ->where('place_uuid = :place_uuid')
            ->setParameter(':place_uuid', $placeId)
            ->execute()
            ->fetchFirstColumn();

        return count($rows) === 1 ? $rows[0] : null;
    }

    public function getDuplicatesOfPlace(string $placeId): ?array
    {
        $duplicates = $this->connection->createQueryBuilder()
            ->select('place_uuid')
            ->from('duplicate_places')
            ->where('canonical = :canonical')
            ->setParameter(':canonical', $placeId)
            ->execute()
            ->fetchFirstColumn();

        return count($duplicates) > 0 ? $duplicates : null;
    }

    public function getPlacesNoLongerInCluster(): array
    {
        // All places that do not exist in duplicate_places_import
        $statement = $this->connection->createQueryBuilder()
            ->select('DISTINCT dp.place_uuid')
            ->from('duplicate_places', 'dp')
            ->leftJoin('dp', 'duplicate_places_import', 'dpi', 'dp.place_uuid = dpi.place_uuid')
            ->where('dpi.place_uuid IS NULL')
            ->execute();

        return $statement->fetchFirstColumn();
    }

    public function getClustersToBeRemoved(): array
    {
        // All clusters that do not exist in duplicate_places_import
        $statement = $this->connection->createQueryBuilder()
            ->select('DISTINCT dp.cluster_id')
            ->from('duplicate_places', 'dp')
            ->leftJoin('dp', 'duplicate_places_import', 'dpi', 'dp.cluster_id = dpi.cluster_id')
            ->where('dpi.cluster_id IS NULL')
            ->orderBy('dp.cluster_id', 'asc')
            ->execute();

        return $statement->fetchFirstColumn();
    }

    public function countPlacesInDuplicatePlacesImport(string $placeId): int
    {
        $statement = $this->connection->createQueryBuilder()
            ->select('count(*) as total')
            ->from('duplicate_places_import')
            ->where('place_uuid = :place_id')
            ->setParameter(':place_id', $placeId)
            ->execute();

        return $statement->fetchAssociative()['total'];
    }

    public function deleteCluster(string $clusterId): void
    {
        $this->connection->createQueryBuilder()
            ->delete('duplicate_places')
            ->where('cluster_id = :cluster_id')
            ->setParameter(':cluster_id', $clusterId)
            ->execute();
    }
}
