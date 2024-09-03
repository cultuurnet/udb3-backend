<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Canonical;

use CultuurNet\UDB3\Place\DuplicatePlace\Dto\ClusterChangeResult;
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
            ->select('cluster_id')
            ->from('duplicate_places')
            ->having('count(*) = sum(canonical IS NULL)')
            ->orderBy('cluster_id')
            ->groupBy('cluster_id')
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
                ':place_uuid' => $canonical,
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
        $statement = $this->connection->executeQuery('
           SELECT dp.*
           FROM duplicate_places dp
           LEFT JOIN duplicate_places_import dpi
           ON dpi.cluster_id = dp.cluster_id AND dpi.place_uuid = dp.place_uuid
           WHERE dpi.cluster_id IS NULL
           ORDER BY dp.cluster_id asc, dp.place_uuid asc
        ');

        return $this->processRawToClusterRecord($statement->fetchAllAssociative());
    }

    /** @return ClusterRecord[] */
    public function calculateNotYetInCluster(): array
    {
        $statement = $this->connection->executeQuery('
           SELECT dpi.*
           FROM duplicate_places_import dpi
           LEFT JOIN duplicate_places dp
           ON dpi.cluster_id = dp.cluster_id AND dpi.place_uuid = dp.place_uuid
           WHERE dp.cluster_id IS NULL
           ORDER BY dp.cluster_id asc, dp.place_uuid asc
        ');

        return $this->processRawToClusterRecord($statement->fetchAllAssociative());
    }

    /** @return ClusterRecord[] */
    private function processRawToClusterRecord(array $data): array
    {
        return array_map(function ($row): ClusterRecord {
            return ClusterRecord::fromArray($row);
        }, $data);
    }

    public function addToDuplicatePlacesRemovedFromCluster(string $clusterId): void
    {
        $this->connection->executeQuery('INSERT INTO duplicate_places_removed_from_cluster SET cluster_id  = :cluster_id', [':cluster_id' => $clusterId]);
    }

    public function addToDuplicatePlaces(string $clusterId, string $placeUuid, string $canonical = null): void
    {
        $this->connection->executeQuery(
            'INSERT INTO duplicate_places SET cluster_id = :cluster_id, place_uuid = :place_uuid, canonical = :canonical',
            ['cluster_id' => $clusterId, ':place_uuid' => $placeUuid, 'canonical' => $canonical]
        );
    }

    public function calculateHowManyClustersHaveChanged(): ClusterChangeResult
    {
        $statement = $this->connection->executeQuery('SELECT
            not_in_duplicate,
            not_in_import
        FROM
            (SELECT COUNT(*) AS not_in_duplicate
             FROM duplicate_places_import dpi
             LEFT JOIN duplicate_places dp
             ON dpi.cluster_id = dp.cluster_id AND dpi.place_uuid = dp.place_uuid
             WHERE dp.cluster_id IS NULL) AS diff_import_to_duplicate,
            (SELECT COUNT(*) AS not_in_import
             FROM duplicate_places dp
             LEFT JOIN duplicate_places_import dpi
             ON dp.cluster_id = dpi.cluster_id AND dp.place_uuid = dpi.place_uuid
             WHERE dpi.cluster_id IS NULL) AS diff_duplicate_to_import;
        ');

        return ClusterChangeResult::fromArray($statement->fetchAssociative());
    }

    public function howManyPlacesAreToBeImported(): int
    {
        $statement = $this->connection->executeQuery('SELECT count(*) as total FROM duplicate_places_import');

        $count = $statement->fetchAssociative();

        return (int)$count['total'];
    }
}
