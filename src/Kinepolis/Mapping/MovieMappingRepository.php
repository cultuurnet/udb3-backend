<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Kinepolis\Mapping;

use Doctrine\DBAL\Connection;

final class MovieMappingRepository implements MappingRepository
{
    private Connection $connection;

    private const TABLE = 'kinepolis_movie_mapping';

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getByMovieId(string $movieId): ?string
    {
        $result = $this->connection->createQueryBuilder()
            ->select('event_id')
            ->from(self::TABLE)
            ->where('movie_id = :movie_id')
            ->setParameter('movie_id', $movieId)
            ->execute()
            ->fetchFirstColumn();

        return count($result) === 0 ? null : $result[0];
    }

    public function create(string $eventId, string $movieId): void
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
