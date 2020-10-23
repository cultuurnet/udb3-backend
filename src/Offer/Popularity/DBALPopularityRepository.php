<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Popularity;

use Doctrine\DBAL\Connection;

class DBALPopularityRepository implements PopularityRepository
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function get(string $offerId): Popularity
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $popularity = $queryBuilder
            ->select('popularity')
            ->from('offer_popularity')
            ->where('offer_id = :offer_id')
            ->setParameter(':offer_id', $offerId)
            ->execute()
            ->fetchColumn();

        return new Popularity((int) $popularity);
    }
}
