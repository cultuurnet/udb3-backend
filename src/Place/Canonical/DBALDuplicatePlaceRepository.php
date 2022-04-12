<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Canonical;

use Doctrine\DBAL\Connection;
use PDO;

class DBALDuplicatePlaceRepository implements DuplicatePlaceRepository
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getClusterIds(): array
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $result = $queryBuilder
            ->select('DISTINCT cluster_id')
            ->from('duplicate_places')
            ->execute()
            ->fetchAll(PDO::FETCH_COLUMN);

        return array_map('intval', $result);
    }

    public function getCluster(int $clusterId): array
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        return $queryBuilder
            ->select('place_uuid')
            ->from('duplicate_places')
            ->where('cluster_id = :cluster_id')
            ->setParameter(':cluster_id', $clusterId)
            ->execute()
            ->fetchAll(PDO::FETCH_COLUMN);
    }
}
