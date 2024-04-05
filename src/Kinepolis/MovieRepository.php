<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Kinepolis;

use Doctrine\DBAL\Connection;

final class MovieRepository
{
    private Connection $connection;

    private const TABLE = 'movie_mapping';

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getEventIdByMovieId(string $movieId): ?string
    {
        $result = $this->connection->createQueryBuilder()
            ->select('event_id')
            ->from(self::TABLE)
            ->where('movie_id' . ' = :movie_id')
            ->setParameter('movie_id', $movieId)
            ->execute()
            ->fetchFirstColumn();

        return sizeof($result) === 0 ? null : $result[0];
    }

    public function addRelation(string $eventId, string $movieId): void
    {
        $this->connection->insert(
            self::TABLE,
            [
                'event_id' => $eventId,
                'movie_id' => $movieId,
            ]
        );
    }
}
