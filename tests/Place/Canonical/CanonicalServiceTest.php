<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Canonical;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use CultuurNet\UDB3\Event\ReadModel\Relations\Doctrine\DBALEventRelationsRepository;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\Doctrine\DBALReadRepository;
use CultuurNet\UDB3\Place\Canonical\Exception\MuseumPassAndUiTPassInSameCluster;
use CultuurNet\UDB3\Place\Canonical\Exception\MuseumPassNotUniqueInCluster;
use CultuurNet\UDB3\Place\Canonical\Exception\UiTPassNotUniqueInCluster;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\TestCase;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;

class CanonicalServiceTest extends TestCase
{
    use DBALTestConnectionTrait;

    private const UITPAS_LABEL = 'UiTPas';
    private const UITPAS_LABEL_GENT = 'UiTPAS Gent';
    private CanonicalService $canonicalService;

    private string $museumPassPlaceId;
    private string $mostEventsPlaceId;
    private string $UiTPASPlaceId;
    private string $oldestPlaceId;

    public function setUp(): void
    {
        $this->setUpDatabase();

        $this->oldestPlaceId = '8717c43d-026f-42e9-9ea9-799623c5763c';
        $this->museumPassPlaceId = '901e23fe-b393-4cc6-9307-8e3e3f2ea77f';
        $anotherMuseumPassPlaceId = '526605d3-7cc4-4607-97a4-065896253f42';
        $this->mostEventsPlaceId = '34621f3b-b626-4672-be7c-33972ac13791';
        $this->UiTPASPlaceId = Uuid::uuid4()->toString();
        $anotherUiTPASPlaceId = Uuid::uuid4()->toString();

        $this->getConnection()->insert(
            'duplicate_places',
            [
                'cluster_id' => 'cluster_with_one_uitpas_location',
                'place_uuid' => $this->UiTPASPlaceId,
            ]
        );
        $this->getConnection()->insert(
            'duplicate_places',
            [
                'cluster_id' => 'cluster_with_one_uitpas_location',
                'place_uuid' => $this->oldestPlaceId,
            ]
        );

        $this->getConnection()->insert(
            'duplicate_places',
            [
                'cluster_id' => 'cluster_uitpas',
                'place_uuid' => $this->UiTPASPlaceId,
            ]
        );
        $this->getConnection()->insert(
            'duplicate_places',
            [
                'cluster_id' => 'cluster_uitpas',
                'place_uuid' => $anotherUiTPASPlaceId,
            ]
        );

        $this->getConnection()->insert(
            'duplicate_places',
            [
                'cluster_id' => 'cluster_mpm_and_uitpas',
                'place_uuid' => $this->museumPassPlaceId,
            ]
        );
        $this->getConnection()->insert(
            'duplicate_places',
            [
                'cluster_id' => 'cluster_mpm_and_uitpas',
                'place_uuid' => $this->UiTPASPlaceId,
            ]
        );

        $this->getConnection()->insert(
            'duplicate_places',
            [
                'cluster_id' => 'cluster_1',
                'place_uuid' => '5d202668-7b6f-4848-9271-f0e0474f7922',
            ]
        );
        $this->getConnection()->insert(
            'duplicate_places',
            [
                'cluster_id' => 'cluster_1',
                'place_uuid' => $this->museumPassPlaceId,
            ]
        );
        $this->getConnection()->insert(
            'duplicate_places',
            [
                'cluster_id' => 'cluster_1',
                'place_uuid' => 'b22d5d76-dceb-4583-8947-e1183a93c10d',
            ]
        );
        $this->getConnection()->insert(
            'duplicate_places',
            [
                'cluster_id' => 'cluster_2',
                'place_uuid' => $anotherMuseumPassPlaceId,
            ]
        );
        $this->getConnection()->insert(
            'duplicate_places',
            [
                'cluster_id' => 'cluster_2',
                'place_uuid' => $this->museumPassPlaceId,
            ]
        );
        $this->getConnection()->insert(
            'duplicate_places',
            [
                'cluster_id' => 'cluster_2',
                'place_uuid' => '5d202668-7b6f-4848-9271-f0e0474f7922',
            ]
        );
        $this->getConnection()->insert(
            'duplicate_places',
            [
                'cluster_id' => 'cluster_2',
                'place_uuid' => 'b22d5d76-dceb-4583-8947-e1183a93c10d',
            ]
        );
        $this->getConnection()->insert(
            'duplicate_places',
            [
                'cluster_id' => 'cluster_3',
                'place_uuid' => '5d202668-7b6f-4848-9271-f0e0474f7922',
            ]
        );
        $this->getConnection()->insert(
            'duplicate_places',
            [
                'cluster_id' => 'cluster_3',
                'place_uuid' => $this->mostEventsPlaceId,
            ]
        );
        $this->getConnection()->insert(
            'duplicate_places',
            [
                'cluster_id' => 'cluster_3',
                'place_uuid' => 'b22d5d76-dceb-4583-8947-e1183a93c10d',
            ]
        );

        $this->getConnection()->insert(
            'duplicate_places',
            [
                'cluster_id' => 'cluster_4',
                'place_uuid' => $this->oldestPlaceId,
            ]
        );

        $documentRepository = new InMemoryDocumentRepository();

        $this->getConnection()->insert(
            'labels_relations',
            [
                'labelName' => self::UITPAS_LABEL,
                'relationType' => 'Place',
                'relationId'=> $this->UiTPASPlaceId,
                'imported' => '0',
            ]
        );

        $this->getConnection()->insert(
            'labels_relations',
            [
                'labelName' => self::UITPAS_LABEL_GENT,
                'relationType' => 'Place',
                'relationId'=> $anotherUiTPASPlaceId,
                'imported' => '0',
            ]
        );

        $this->getConnection()->insert(
            'labels_relations',
            [
                'labelName' => 'museumPASSmusees',
                'relationType' => 'Place',
                'relationId'=> $this->museumPassPlaceId,
                'imported' => '0',
            ]
        );

        $this->getConnection()->insert(
            'labels_relations',
            [
                'labelName' => 'museumPASSmusees',
                'relationType' => 'Place',
                'relationId'=> $anotherMuseumPassPlaceId,
                'imported' => '0',
            ]
        );

        $this->getConnection()->insert(
            'event_relations',
            [
                'event' => 'cb11d320-17dc-41f8-831a-8b9d8208ea80',
                'organizer' => 'eb89d990-8f5b-46be-a548-c87a300a54c8',
                'place'=> $this->museumPassPlaceId,
            ]
        );
        $this->getConnection()->insert(
            'event_relations',
            [
                'event' => '86e4540d-eed2-4cc5-8e08-a9f684deb03f',
                'organizer' => 'eb89d990-8f5b-46be-a548-c87a300a54c8',
                'place'=> $this->museumPassPlaceId,
            ]
        );
        $this->getConnection()->insert(
            'event_relations',
            [
                'event' => 'bf1ba6c5-6d02-4c08-ab62-84ce8aa214a0',
                'organizer' => 'eb89d990-8f5b-46be-a548-c87a300a54c8',
                'place'=> $this->mostEventsPlaceId,
            ]
        );

        for ($i = 0; $i < 10; $i++) {
            $placeId = '4b4ca084-b78e-474f-b868-6f9df2d20df' . $i;

            $this->getConnection()->insert(
                'duplicate_places',
                [
                    'cluster_id' => 'cluster_4',
                    'place_uuid' => $placeId,
                ]
            );

            $jsonDocument = new JsonDocument(
                $placeId,
                Json::encode(['@id' => $placeId, 'created' => '2018-12-0' . $i . 'T19:40:58+00:00'])
            );
            $documentRepository->save($jsonDocument);
        }

        $oldestJsonDocument = new JsonDocument(
            $this->oldestPlaceId,
            Json::encode(['@id' => $this->oldestPlaceId, 'created' => '2017-12-09T19:40:58+00:00'])
        );
        $documentRepository->save($oldestJsonDocument);

        $this->canonicalService = new CanonicalService(
            'museumPASSmusees',
            [self::UITPAS_LABEL, self::UITPAS_LABEL_GENT],
            new DBALDuplicatePlaceRepository($this->getConnection()),
            new DBALEventRelationsRepository(
                $this->getConnection()
            ),
            new DBALReadRepository(
                $this->getConnection(),
                'labels_relations'
            ),
            $documentRepository
        );
    }

    /**
     * @test
     */
    public function it_will_return_the_MPM_place(): void
    {
        $canonicalId = $this->canonicalService->getCanonical('cluster_1');

        $this->assertEquals(
            $this->museumPassPlaceId,
            $canonicalId
        );
    }

    /**
     * @test
     */
    public function it_will_get_the_place_with_most_events(): void
    {
        $canonicalId = $this->canonicalService->getCanonical('cluster_3');

        $this->assertEquals(
            $this->mostEventsPlaceId,
            $canonicalId
        );
    }

    /**
     * @test
     */
    public function it_will_throw_an_exception_when_cluster_contains_2_MPM_places(): void
    {
        $this->expectException(MuseumPassNotUniqueInCluster::class);
        $this->expectExceptionMessage('Cluster cluster_2 contains 2 MuseumPass places');

        $this->canonicalService->getCanonical('cluster_2');
    }

    /**
     * @test
     */
    public function it_will_throw_an_exception_when_cluster_contains_2_UITPAS_places(): void
    {
        $this->expectException(UiTPassNotUniqueInCluster::class);
        $this->expectExceptionMessage('Cluster cluster_uitpas contains 2 UiTPAS places');

        $this->canonicalService->getCanonical('cluster_uitpas');
    }

    /**
     * @test
     */
    public function it_will_throw_an_exception_when_cluster_contains_an_UITPAS_AND_MPM_place(): void
    {
        $this->expectException(MuseumPassAndUiTPassInSameCluster::class);
        $this->expectExceptionMessage('Cluster cluster_mpm_and_uitpas contains 1 MuseumPass places and 1 UiTPAS places');
        $this->canonicalService->getCanonical('cluster_mpm_and_uitpas');
    }

    /**
     * @test
     */
    public function it_will_get_the_oldest_place_if_equal_nr_of_events(): void
    {
        $canonicalId = $this->canonicalService->getCanonical('cluster_4');

        $this->assertEquals(
            $this->oldestPlaceId,
            $canonicalId
        );
    }

    /**
     * @test
     */
    public function it_will_get_uitpas_location(): void
    {
        $canonicalId = $this->canonicalService->getCanonical('cluster_with_one_uitpas_location');

        $this->assertEquals(
            $this->UiTPASPlaceId,
            $canonicalId
        );
    }
}
