<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Canonical;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use PHPUnit\Framework\TestCase;

class DBALDuplicatePlaceRepositoryTest extends TestCase
{
    use DBALTestConnectionTrait;

    private DBALDuplicatePlaceRepository $duplicatePlaceRepository;

    public function setUp(): void
    {
        $this->setUpDatabase();

        $this->getConnection()->insert(
            'duplicate_places',
            [
                'cluster_id' => 'cluster_1',
                'place_uuid' => '19ce6565-76be-425d-94d6-894f84dd2947',
            ]
        );
        $this->getConnection()->insert(
            'duplicate_places',
            [
                'cluster_id' => 'cluster_1',
                'place_uuid' => '1accbcfb-3b22-4762-bc13-be0f67fd3116',
            ]
        );
        $this->getConnection()->insert(
            'duplicate_places',
            [
                'cluster_id' => 'cluster_1',
                'place_uuid' => '526605d3-7cc4-4607-97a4-065896253f42',
            ]
        );
        $this->getConnection()->insert(
            'duplicate_places',
            [
                'cluster_id' => 'cluster_2',
                'place_uuid' => '4a355db3-c3f9-4acc-8093-61b333a3aefb',
            ]
        );
        $this->getConnection()->insert(
            'duplicate_places',
            [
                'cluster_id' => 'cluster_2',
                'place_uuid' => '64901efc-6bd7-4e9d-8916-fcdeb5b1c8ad',
            ]
        );

        $this->duplicatePlaceRepository = new DBALDuplicatePlaceRepository($this->getConnection());
    }

    private function insertDuplicatePlaceImport(string $clusterId, string $placeUuid): void
    {
        $qb = $this->getConnection()->createQueryBuilder();
        $qb->insert('duplicate_places_import')
            ->setValue('cluster_id', ':cluster_id')
            ->setValue('place_uuid', ':place_uuid')
            ->setParameter('cluster_id', $clusterId)
            ->setParameter('place_uuid', $placeUuid)
            ->execute();
    }

    /**
     * @test
     */
    public function it_can_return_clusterIds_without_canonicals(): void
    {
        $this->getConnection()->insert(
            'duplicate_places',
            [
                'cluster_id' => 'cluster_2',
                'place_uuid' => 'e90c0acd-f153-4b35-bd4d-d3ce2d535332',
                'canonical' => 'e90c0acd-f153-4b35-bd4d-d3ce2d535332',
            ]
        );

        $clusterIds = $this->duplicatePlaceRepository->getClusterIdsWithoutCanonical();

        $this->assertEquals(['cluster_1'], $clusterIds);
    }

    /**
     * @test
     */
    public function it_can_return_placeIds(): void
    {
        $clusterIds = $this->duplicatePlaceRepository->getPlacesInCluster('cluster_1');
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
        $this->duplicatePlaceRepository->setCanonicalOnCluster('cluster_1', '1accbcfb-3b22-4762-bc13-be0f67fd3116');
        $this->duplicatePlaceRepository->setCanonicalOnCluster('cluster_2', '64901efc-6bd7-4e9d-8916-fcdeb5b1c8ad');

        $actualRows = $this->getConnection()->createQueryBuilder()
            ->select('*')
            ->from('duplicate_places')
            ->orderBy('place_uuid')
            ->execute()
            ->fetchAllNumeric();

        $this->assertEquals(
            [
                ['cluster_1', '19ce6565-76be-425d-94d6-894f84dd2947', '1accbcfb-3b22-4762-bc13-be0f67fd3116'],
                ['cluster_1', '1accbcfb-3b22-4762-bc13-be0f67fd3116', null],
                ['cluster_2', '4a355db3-c3f9-4acc-8093-61b333a3aefb', '64901efc-6bd7-4e9d-8916-fcdeb5b1c8ad'],
                ['cluster_1', '526605d3-7cc4-4607-97a4-065896253f42', '1accbcfb-3b22-4762-bc13-be0f67fd3116'],
                ['cluster_2', '64901efc-6bd7-4e9d-8916-fcdeb5b1c8ad', null],
            ],
            $actualRows
        );
    }

    /**
     * @test
     */
    public function it_can_get_the_canonical_of_a_place(): void
    {
        $this->duplicatePlaceRepository->setCanonicalOnCluster('cluster_1', '1accbcfb-3b22-4762-bc13-be0f67fd3116');
        $this->duplicatePlaceRepository->setCanonicalOnCluster('cluster_2', '64901efc-6bd7-4e9d-8916-fcdeb5b1c8ad');

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
        $this->duplicatePlaceRepository->setCanonicalOnCluster('cluster_1', '1accbcfb-3b22-4762-bc13-be0f67fd3116');
        $this->duplicatePlaceRepository->setCanonicalOnCluster('cluster_2', '64901efc-6bd7-4e9d-8916-fcdeb5b1c8ad');

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

    public function test_places_no_longer_in_cluster(): void
    {
        $this->insertDuplicatePlaceImport('cluster_2', '19ce6565-76be-425d-94d6-894f84dd2947');

        $this->assertEquals(
            [
                '1accbcfb-3b22-4762-bc13-be0f67fd3116',
                '4a355db3-c3f9-4acc-8093-61b333a3aefb',
                '526605d3-7cc4-4607-97a4-065896253f42',
                '64901efc-6bd7-4e9d-8916-fcdeb5b1c8ad',
            ],
            $this->duplicatePlaceRepository->getPlacesNoLongerInCluster()
        );
    }

    public function test_clusters_to_be_removed(): void
    {
        $this->insertDuplicatePlaceImport('cluster_2', '19ce6565-76be-425d-94d6-894f84dd2947');

        $this->assertEquals(
            [
                'cluster_1',
            ],
            $this->duplicatePlaceRepository->getClustersToBeRemoved()
        );
    }

    public function test_delete_cluster(): void
    {
        $this->duplicatePlaceRepository->deleteCluster('cluster_1');

        $qb = $this->getConnection()->createQueryBuilder();
        $qb->select('count(*) as total')
            ->from('duplicate_places')
            ->where('cluster_id = :cluster_id')
            ->setParameter('cluster_id', 'cluster_1');
        $raw = $qb->execute()->fetchAssociative();

        $this->assertEquals(0, $raw['total']);
    }

    public function test_add_to_duplicate_places(): void
    {
        $placeUuid = '19ce6565-76be-425d-94d6-894f84dd2947';

        $this->duplicatePlaceRepository->addToDuplicatePlaces(new ClusterRecordRow('cluster_new', $placeUuid));

        $raw = $this->connection->fetchAssociative('select count(*) as total from duplicate_places where cluster_id = :cluster_id and place_uuid = :place_uuid', ['cluster_id' => 'cluster_new', 'place_uuid' => $placeUuid]);
        $this->assertEquals(1, $raw['total']);
    }
}
