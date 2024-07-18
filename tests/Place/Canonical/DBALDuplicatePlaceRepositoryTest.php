<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Canonical;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use PHPUnit\Framework\TestCase;

class DBALDuplicatePlaceRepositoryTest extends TestCase
{
    use DBALTestConnectionTrait;

    private DBALDuplicatePlaceRepository $duplicatePlaceRepository;

    public function setUp(): void
    {
        $table = new Table('duplicate_places');
        $table->addColumn('cluster_id', Types::BIGINT)->setNotnull(true);
        $table->addColumn('place_uuid', Types::GUID)->setLength(36)->setNotnull(true);
        $table->addColumn('canonical', Types::GUID)->setLength(36)->setNotnull(false)->setDefault(null);
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
        $clusterIds = $this->duplicatePlaceRepository->getPlacesInCluster('1');

        $this->assertEquals(
            [
                '19ce6565-76be-425d-94d6-894f84dd2947',
                '1accbcfb-3b22-4762-bc13-be0f67fd3116',
                '526605d3-7cc4-4607-97a4-065896253f42',
            ],
            $clusterIds
        );
    }

    /**
     * @test
     */
    public function it_can_set_the_canonical_of_a_cluster(): void
    {
        $this->duplicatePlaceRepository->setCanonicalOnCluster('1', '1accbcfb-3b22-4762-bc13-be0f67fd3116');
        $this->duplicatePlaceRepository->setCanonicalOnCluster('2', '64901efc-6bd7-4e9d-8916-fcdeb5b1c8ad');

        $actualRows = $this->connection->createQueryBuilder()
            ->select('*')
            ->from('duplicate_places')
            ->execute()
            ->fetchAllNumeric();

        $this->assertEquals(
            [
                ['1', '19ce6565-76be-425d-94d6-894f84dd2947', '1accbcfb-3b22-4762-bc13-be0f67fd3116'],
                ['1', '1accbcfb-3b22-4762-bc13-be0f67fd3116', null],
                ['1', '526605d3-7cc4-4607-97a4-065896253f42', '1accbcfb-3b22-4762-bc13-be0f67fd3116'],
                ['2', '4a355db3-c3f9-4acc-8093-61b333a3aefb', '64901efc-6bd7-4e9d-8916-fcdeb5b1c8ad'],
                ['2', '64901efc-6bd7-4e9d-8916-fcdeb5b1c8ad', null],
            ],
            $actualRows
        );
    }

    /**
     * @test
     */
    public function it_can_get_the_canonical_of_a_place(): void
    {
        $this->duplicatePlaceRepository->setCanonicalOnCluster('1', '1accbcfb-3b22-4762-bc13-be0f67fd3116');
        $this->duplicatePlaceRepository->setCanonicalOnCluster('2', '64901efc-6bd7-4e9d-8916-fcdeb5b1c8ad');

        $this->assertEquals(
            '1accbcfb-3b22-4762-bc13-be0f67fd3116',
            $this->duplicatePlaceRepository->getCanonicalOfPlace('19ce6565-76be-425d-94d6-894f84dd2947')
        );
        $this->assertEquals(
            null,
            $this->duplicatePlaceRepository->getCanonicalOfPlace('1accbcfb-3b22-4762-bc13-be0f67fd3116')
        );
        $this->assertEquals(
            '1accbcfb-3b22-4762-bc13-be0f67fd3116',
            $this->duplicatePlaceRepository->getCanonicalOfPlace('526605d3-7cc4-4607-97a4-065896253f42')
        );

        $this->assertEquals(
            '64901efc-6bd7-4e9d-8916-fcdeb5b1c8ad',
            $this->duplicatePlaceRepository->getCanonicalOfPlace('4a355db3-c3f9-4acc-8093-61b333a3aefb')
        );
        $this->assertEquals(
            null,
            $this->duplicatePlaceRepository->getCanonicalOfPlace('64901efc-6bd7-4e9d-8916-fcdeb5b1c8ad')
        );
    }

    /**
     * @test
     */
    public function it_can_get_the_duplicates_of_a_place(): void
    {
        $this->duplicatePlaceRepository->setCanonicalOnCluster('1', '1accbcfb-3b22-4762-bc13-be0f67fd3116');
        $this->duplicatePlaceRepository->setCanonicalOnCluster('2', '64901efc-6bd7-4e9d-8916-fcdeb5b1c8ad');

        $this->assertNull(
            $this->duplicatePlaceRepository->getDuplicatesOfPlace('19ce6565-76be-425d-94d6-894f84dd2947')
        );
        $this->assertEquals(
            [
                '19ce6565-76be-425d-94d6-894f84dd2947',
                '526605d3-7cc4-4607-97a4-065896253f42',
            ],
            $this->duplicatePlaceRepository->getDuplicatesOfPlace('1accbcfb-3b22-4762-bc13-be0f67fd3116')
        );
        $this->assertNull(
            $this->duplicatePlaceRepository->getDuplicatesOfPlace('526605d3-7cc4-4607-97a4-065896253f42')
        );

        $this->assertNull(
            $this->duplicatePlaceRepository->getDuplicatesOfPlace('4a355db3-c3f9-4acc-8093-61b333a3aefb')
        );
        $this->assertEquals(
            [
                '4a355db3-c3f9-4acc-8093-61b333a3aefb',
            ],
            $this->duplicatePlaceRepository->getDuplicatesOfPlace('64901efc-6bd7-4e9d-8916-fcdeb5b1c8ad')
        );
    }
}
