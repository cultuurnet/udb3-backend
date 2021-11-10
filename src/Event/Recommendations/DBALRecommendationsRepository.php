<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Recommendations;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;

final class DBALRecommendationsRepository implements RecommendationsRepository
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getByEvent(string $eventId): Recommendations
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $recommendationRows = $queryBuilder
            ->select('recommended_event_id', 'score')
            ->from('event_recommendations')
            ->where('event_id = :event_id')
            ->setParameter(':event_id', $eventId)
            ->execute()
            ->fetchAll(FetchMode::NUMERIC);

        return $this->createRecommendations($recommendationRows);
    }

    public function getByRecommendedEvent(string $recommendedEventId): Recommendations
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $recommendationRows = $queryBuilder
            ->select('event_id', 'score')
            ->from('event_recommendations')
            ->where('recommended_event_id = :recommended_event_id')
            ->setParameter(':recommended_event_id', $recommendedEventId)
            ->execute()
            ->fetchAll(FetchMode::NUMERIC);

        return $this->createRecommendations($recommendationRows);
    }

    private function createRecommendations(array $recommendationRows): Recommendations
    {
        return new Recommendations(
            ...array_map(
                fn (array $recommendationRow) => new Recommendation($recommendationRow[0], (float) $recommendationRow[1]),
                $recommendationRows
            )
        );
    }
}
