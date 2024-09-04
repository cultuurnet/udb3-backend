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

    public function getClusterIdsWithoutCanonical(): array
    {
        // We need to use a group by because the canonical place itself will always have the canonical set to NULL.
        // Why? Because this means they have not been processed yet and should be picked up by the CLI command.
        return $this->connection->createQueryBuilder()
            ->select('cluster_id')
            ->from('duplicate_places')
            ->having('count(*) = sum(canonical IS NULL)')
            ->orderBy('cluster_id')
            ->groupBy('cluster_id')
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

    /** @return PlaceWithCluster[] */
    public function getPlacesWithCluster(): array
    {
        // All clusters that do not exist in duplicate_places_import
        $statement = $this->connection->createQueryBuilder()
            ->select('dpi.cluster_id, dpi.place_uuid')
            ->from('duplicate_places_import', 'dpi')
            ->leftJoin('dpi', 'duplicate_places', 'dp', 'dp.cluster_id = dpi.cluster_id')
            ->where('dp.cluster_id IS NULL')
            ->orderBy('dpi.cluster_id', 'asc')
            ->execute();

        return array_map(static function (array $row) {
            return new PlaceWithCluster($row['cluster_id'], $row['place_uuid']);
        }, $statement->fetchAllAssociative());
    }

    public function deleteCluster(string $clusterId): void
    {
        $this->connection->createQueryBuilder()
            ->delete('duplicate_places')
            ->where('cluster_id = :cluster_id')
            ->setParameter(':cluster_id', $clusterId)
            ->execute();
    }

    public function addToDuplicatePlaces(PlaceWithCluster $clusterRecordRow): void
    {
        $this->connection->createQueryBuilder()
            ->insert('duplicate_places')
            ->setValue('cluster_id', ':cluster_id')
            ->setValue('place_uuid', ':place_uuid')
            ->setValue('canonical', ':canonical')
            ->setParameters([
                ':cluster_id' => $clusterRecordRow->getClusterId(),
                ':place_uuid' => $clusterRecordRow->getPlaceUuid(),
                ':canonical' => $clusterRecordRow->getCanonical(),
            ])
            ->execute();
    }

    public function howManyPlacesAreToBeImported(): int
    {
        // COUNT from `duplicate_places_import` not present in `duplicate_places`
        $result = $this->connection->createQueryBuilder()
            ->select('COUNT(*) AS not_in_duplicate')
            ->from('duplicate_places_import', 'dpi')
            ->leftJoin('dpi', 'duplicate_places', 'dp', 'dpi.cluster_id = dp.cluster_id AND dpi.place_uuid = dp.place_uuid')
            ->where('dp.cluster_id IS NULL')
            ->execute();

        $a = $result->fetchOne();

        return (int)($a ?? 0);
    }

    public function howManyPlacesAreToBeDeleted(): int
    {
        // COUNT from `duplicate_places` not present in `duplicate_places_import`
        $result = $this->connection->createQueryBuilder()
            ->select('COUNT(*) AS not_in_import')
            ->from('duplicate_places', 'dp')
            ->leftJoin('dp', 'duplicate_places_import', 'dpi', 'dp.cluster_id = dpi.cluster_id AND dp.place_uuid = dpi.place_uuid')
            ->where('dpi.cluster_id IS NULL')
            ->execute();

        return (int)($result->fetchOne() ?? 0);
    }
}
