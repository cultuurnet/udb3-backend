<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Canonical;

use CultuurNet\UDB3\Event\ReadModel\Relations\RepositoryInterface;
use Doctrine\DBAL\Connection;

class DBALDuplicatePlaceRepository implements DuplicatePlaceRepository
{
    private Connection $connection;

    private string $museumpass;

    private RepositoryInterface $eventRelationsRepository;

    public function __construct(Connection $connection, string $museumpass, RepositoryInterface $eventRelationsRepository)
    {
        $this->connection = $connection;
        $this->museumpass = $museumpass;
        $this->eventRelationsRepository = $eventRelationsRepository;
    }

    public function getClusterIds(): array
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        return $queryBuilder
            ->select('DISTINCT cluster_id')
            ->from('duplicate_places')
            ->execute()
            ->fetchColumn();
    }

    public function getCluster(int $clusterId): PlaceCluster
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $places = $queryBuilder
            ->select('place_uuid')
            ->from('duplicate_places')
            ->where('cluster_id = :cluster_id')
            ->setParameter(':cluster_id', $clusterId)
            ->execute()
            ->fetchColumn();

        return new PlaceCluster($clusterId, $places);
    }

    public function getCanonical(array $placeIds): string
    {
        if (count($this->checkMuseumPass($placeIds)) === 1) {
            return $this->checkMuseumPass($placeIds)[0];
        }

        // Temp
        return $placeIds[1];
    }

    private function checkMuseumPass($placeIds): array
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $museaIds = $queryBuilder
            ->select('relationId')
            ->from('labels_relations')
            ->where('labelName = :labelName')
            ->andWhere('relationType = :relationType')
            ->setParameters(
                [':labelName', $this->museumpass],
                [':relationType', 'Place']
            )
            ->execute()
            ->fetchColumn();

        return array_intersect($placeIds, $museaIds);
    }

    private function checkEvents($placeIds): array
    {

        //$this->eventRelationsRepository->getEventsLocatedAtPlace()
        return [];
    }
}
