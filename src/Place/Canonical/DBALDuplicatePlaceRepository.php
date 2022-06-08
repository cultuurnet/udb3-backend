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
        $result = $this->connection->createQueryBuilder()
            ->select('DISTINCT cluster_id')
            ->from('duplicate_places')
            ->execute()
            ->fetchAll(PDO::FETCH_COLUMN);

        return array_map('intval', $result);
    }

    public function getCluster(int $clusterId): array
    {
        return $this->connection->createQueryBuilder()
            ->select('place_uuid')
            ->from('duplicate_places')
            ->where('cluster_id = :cluster_id')
            ->setParameter(':cluster_id', $clusterId)
            ->execute()
            ->fetchAll(PDO::FETCH_COLUMN);
    }

    public function setCanonicalOnCluster(int $clusterId, string $canonical): void
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
}
