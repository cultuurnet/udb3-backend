<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Canonical;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\TestCase;

class DBALDuplicatePlaceRepositoryTest extends TestCase
{
    use DBALTestConnectionTrait;

    private DBALDuplicatePlaceRepository $duplicatePlaceRepository;
    public function setUp()
    {
        $table = new Table('duplicate_places');
        $table->addColumn('cluster_id', Type::BIGINT)->setNotnull(true);
        $table->addColumn('place_uuid', Type::GUID)->setLength(36)->setNotnull(true);
        $this->createTable($table);

        $this->getConnection()->insert(
            'duplicate_places',
            [
                'cluster_id' => '1',
                'place_uuid' => '19ce6565-76be-425d-94d6-894f84dd2947',
            ]
        );
        $this->getConnection()->insert(
            'duplicate_places',
            [
                'cluster_id' => '1',
                'place_uuid' => '1accbcfb-3b22-4762-bc13-be0f67fd3116',
            ]
        );
        $this->getConnection()->insert(
            'duplicate_places',
            [
                'cluster_id' => '1',
                'place_uuid' => '526605d3-7cc4-4607-97a4-065896253f42',
            ]
        );
        $this->getConnection()->insert(
            'duplicate_places',
            [
                'cluster_id' => '2',
                'place_uuid' => '4a355db3-c3f9-4acc-8093-61b333a3aefb',
            ]
        );
        $this->getConnection()->insert(
            'duplicate_places',
            [
                'cluster_id' => '2',
                'place_uuid' => '64901efc-6bd7-4e9d-8916-fcdeb5b1c8ad',
            ]
        );

        $this->duplicatePlaceRepository = new DBALDuplicatePlaceRepository($this->getConnection());
    }

    /**
     * @test
     */
    public function it_can_return_clusterIds(): void
    {
        $clusterIds = $this->duplicatePlaceRepository->getClusterIds();

        $this->assertEquals([1,2], $clusterIds);
    }

    /**
     * @test
     */
    public function it_can_return_placeIds(): void
    {
        $clusterIds = $this->duplicatePlaceRepository->getCluster(1);

        $this->assertEquals(
            new PlaceCluster(
                1,
                [
                    '19ce6565-76be-425d-94d6-894f84dd2947',
                    '1accbcfb-3b22-4762-bc13-be0f67fd3116',
                    '526605d3-7cc4-4607-97a4-065896253f42',
                ]
            ),
            $clusterIds
        );
    }
}
