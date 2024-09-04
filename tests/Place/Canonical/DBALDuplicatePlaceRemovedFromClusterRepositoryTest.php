<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Canonical;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use PHPUnit\Framework\TestCase;

class DBALDuplicatePlaceRemovedFromClusterRepositoryTest extends TestCase
{
    use DBALTestConnectionTrait;

    private DBALDuplicatePlacesRemovedFromClusterRepository $repository;

    public function setUp(): void
    {
        $this->setUpDatabase();

        $this->repository = new DBALDuplicatePlacesRemovedFromClusterRepository($this->getConnection());
    }

    public function test_add_to_duplicate_places_removed_from_cluster(): void
    {
        $this->repository->addPlace('64901efc-6bd7-4e9d-8916-fcdeb5b1c8af');

        $raw = $this->connection->fetchAllAssociative('select * from duplicate_places_removed_from_cluster');
        $this->assertEquals([['place_uuid' => '64901efc-6bd7-4e9d-8916-fcdeb5b1c8af']], $raw);
    }

    public function test_get_duplicate_places_removed_from_cluster(): void
    {
        $this->getConnection()->insert(
            'duplicate_places_removed_from_cluster',
            [
                'place_uuid' => 'b22d5d76-dceb-4583-8947-e1183a93c10d',
            ]
        );
        $this->getConnection()->insert(
            'duplicate_places_removed_from_cluster',
            [
                'place_uuid' => '64901efc-6bd7-4e9d-8916-fcdeb5b1c8af',
            ]
        );

        $this->assertEquals([
            '64901efc-6bd7-4e9d-8916-fcdeb5b1c8af',
            'b22d5d76-dceb-4583-8947-e1183a93c10d',
        ], $this->repository->getDuplicatePlacesRemovedFromCluster());
    }
}
