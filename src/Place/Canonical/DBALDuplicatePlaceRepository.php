<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Canonical;

use CultuurNet\UDB3\Place\DuplicatePlace\Dto\ClusterRecord;
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
        $result = $this->connection->createQueryBuilder()
            ->select('DISTINCT cluster_id')
            ->from('duplicate_places')
            ->orderBy('cluster_id')
            ->execute()
            ->fetchFirstColumn();

        return $result;
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
                ':canonical' => $canonical,
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

    /** @return ClusterRecord[] */
    public function calculateNoLongerInCluster(): array
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->select('dp.*')
            ->from('duplicate_places', 'dp')
            ->leftJoin('dp', 'duplicate_places_import', 'dpi', 'dpi.cluster_id = dp.cluster_id AND dpi.place_uuid = dp.place_uuid')
            ->where('dpi.cluster_id IS NULL')
            ->orderBy('dp.cluster_id', 'asc')
            ->addOrderBy('dp.place_uuid', 'asc');

        $statement = $qb->execute();

        return $this->processRawToClusterRecord($statement->fetchAllAssociative());
    }

    public function calculatePlaceInDuplicatePlacesImport(string $placeId): array
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->select('dpi.*')
            ->from('duplicate_places_import', 'dpi')
            ->where('dpi.place_uuid = :place_id')
            ->setParameter(':place_id', $placeId);

        $statement = $qb->execute();

        return $this->processRawToClusterRecord($statement->fetchAllAssociative());
    }

    public function addToDuplicatePlacesRemovedFromCluster(string $placeId): void
    {
        $this->connection->createQueryBuilder()
            ->insert('duplicate_places_removed_from_cluster')
            ->setValue('place_uuid', ':place_uuid')
            ->setParameter(':place_uuid', $placeId)
            ->execute();
    }

    private function processRawToClusterRecord(array $data): array
    {
        return array_map(function ($row): ClusterRecord {
            return ClusterRecord::fromArray($row);
        }, $data);
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
