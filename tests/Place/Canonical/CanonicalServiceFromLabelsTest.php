<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Canonical;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use CultuurNet\UDB3\Event\ReadModel\Relations\Doctrine\DBALEventRelationsRepository;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\Doctrine\DBALReadRepository;
use CultuurNet\UDB3\Place\Canonical\Exception\MultipleCanonicalPlacesInCluster;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\TestCase;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;

final class CanonicalServiceFromLabelsTest extends TestCase
{
    use DBALTestConnectionTrait;

    private const UITPAS_LABEL = 'UiTPas';
    private const UITPAS_LABEL_GENT = 'UiTPAS Gent';
    private const CJM_LABEL = 'cjm-cc';
    private CanonicalServiceFromLabels $canonicalService;

    private string $museumPassPlaceId;
    private string $anotherMuseumPassPlaceId;
    private string $mostEventsPlaceId;
    private string $UiTPASPlaceId;
    private string $anotherUiTPASPlaceId;
    private string $cjmPlaceId;
    private string $oldestPlaceId;

    public function setUp(): void
    {
        $this->setUpDatabase();

        $this->oldestPlaceId = '8717c43d-026f-42e9-9ea9-799623c5763c';
        $this->museumPassPlaceId = '901e23fe-b393-4cc6-9307-8e3e3f2ea77f';
        $this->anotherMuseumPassPlaceId = '526605d3-7cc4-4607-97a4-065896253f42';
        $anotherMuseumPassPlaceId = $this->anotherMuseumPassPlaceId;
        $this->mostEventsPlaceId = '34621f3b-b626-4672-be7c-33972ac13791';
        $this->UiTPASPlaceId = Uuid::uuid4()->toString();
        $this->anotherUiTPASPlaceId = Uuid::uuid4()->toString();
        $anotherUiTPASPlaceId = $this->anotherUiTPASPlaceId;
        $this->cjmPlaceId = Uuid::uuid4()->toString();

        $this->getConnection()->insert('duplicate_places', ['cluster_id' => 'cluster_with_one_uitpas_location', 'place_uuid' => $this->UiTPASPlaceId]);
        $this->getConnection()->insert('duplicate_places', ['cluster_id' => 'cluster_with_one_uitpas_location', 'place_uuid' => $this->oldestPlaceId]);

        $this->getConnection()->insert('duplicate_places', ['cluster_id' => 'cluster_uitpas', 'place_uuid' => $this->UiTPASPlaceId]);
        $this->getConnection()->insert('duplicate_places', ['cluster_id' => 'cluster_uitpas', 'place_uuid' => $anotherUiTPASPlaceId]);

        $this->getConnection()->insert('duplicate_places', ['cluster_id' => 'cluster_mpm_and_uitpas', 'place_uuid' => $this->museumPassPlaceId]);
        $this->getConnection()->insert('duplicate_places', ['cluster_id' => 'cluster_mpm_and_uitpas', 'place_uuid' => $this->UiTPASPlaceId]);

        $this->getConnection()->insert('duplicate_places', ['cluster_id' => 'cluster_1', 'place_uuid' => '5d202668-7b6f-4848-9271-f0e0474f7922']);
        $this->getConnection()->insert('duplicate_places', ['cluster_id' => 'cluster_1', 'place_uuid' => $this->museumPassPlaceId]);
        $this->getConnection()->insert('duplicate_places', ['cluster_id' => 'cluster_1', 'place_uuid' => 'b22d5d76-dceb-4583-8947-e1183a93c10d']);

        $this->getConnection()->insert('duplicate_places', ['cluster_id' => 'cluster_2', 'place_uuid' => $anotherMuseumPassPlaceId]);
        $this->getConnection()->insert('duplicate_places', ['cluster_id' => 'cluster_2', 'place_uuid' => $this->museumPassPlaceId]);
        $this->getConnection()->insert('duplicate_places', ['cluster_id' => 'cluster_2', 'place_uuid' => '5d202668-7b6f-4848-9271-f0e0474f7922']);
        $this->getConnection()->insert('duplicate_places', ['cluster_id' => 'cluster_2', 'place_uuid' => 'b22d5d76-dceb-4583-8947-e1183a93c10d']);

        $this->getConnection()->insert('duplicate_places', ['cluster_id' => 'cluster_3', 'place_uuid' => '5d202668-7b6f-4848-9271-f0e0474f7922']);
        $this->getConnection()->insert('duplicate_places', ['cluster_id' => 'cluster_3', 'place_uuid' => $this->mostEventsPlaceId]);
        $this->getConnection()->insert('duplicate_places', ['cluster_id' => 'cluster_3', 'place_uuid' => 'b22d5d76-dceb-4583-8947-e1183a93c10d']);

        $this->getConnection()->insert('duplicate_places', ['cluster_id' => 'cluster_4', 'place_uuid' => $this->oldestPlaceId]);

        $this->getConnection()->insert('duplicate_places', ['cluster_id' => 'cluster_cjm', 'place_uuid' => $this->cjmPlaceId]);
        $this->getConnection()->insert('duplicate_places', ['cluster_id' => 'cluster_cjm', 'place_uuid' => $this->oldestPlaceId]);

        $this->getConnection()->insert('duplicate_places', ['cluster_id' => 'cluster_cjm_and_mpm', 'place_uuid' => $this->cjmPlaceId]);
        $this->getConnection()->insert('duplicate_places', ['cluster_id' => 'cluster_cjm_and_mpm', 'place_uuid' => $this->museumPassPlaceId]);

        $this->getConnection()->insert('duplicate_places', ['cluster_id' => 'cluster_uitpas_and_cjm_same_place', 'place_uuid' => $this->UiTPASPlaceId]);
        $this->getConnection()->insert('duplicate_places', ['cluster_id' => 'cluster_uitpas_and_cjm_same_place', 'place_uuid' => $this->oldestPlaceId]);

        $documentRepository = new InMemoryDocumentRepository();

        $this->getConnection()->insert('labels_relations', ['labelName' => self::UITPAS_LABEL, 'relationType' => 'Place', 'relationId' => $this->UiTPASPlaceId, 'imported' => '0']);
        $this->getConnection()->insert('labels_relations', ['labelName' => self::UITPAS_LABEL_GENT, 'relationType' => 'Place', 'relationId' => $anotherUiTPASPlaceId, 'imported' => '0']);

        $documentRepository->save(new JsonDocument($this->UiTPASPlaceId, Json::encode(['@id' => $this->UiTPASPlaceId, 'created' => '2019-01-01T00:00:00+00:00'])));
        $documentRepository->save(new JsonDocument($anotherUiTPASPlaceId, Json::encode(['@id' => $anotherUiTPASPlaceId, 'created' => '2020-01-01T00:00:00+00:00'])));
        $documentRepository->save(new JsonDocument($anotherMuseumPassPlaceId, Json::encode(['@id' => $anotherMuseumPassPlaceId, 'created' => '2021-01-01T00:00:00+00:00'])));
        $this->getConnection()->insert('labels_relations', ['labelName' => 'museumPASSmusees', 'relationType' => 'Place', 'relationId' => $this->museumPassPlaceId, 'imported' => '0']);
        $this->getConnection()->insert('labels_relations', ['labelName' => 'museumPASSmusees', 'relationType' => 'Place', 'relationId' => $anotherMuseumPassPlaceId, 'imported' => '0']);
        $this->getConnection()->insert('labels_relations', ['labelName' => self::CJM_LABEL, 'relationType' => 'Place', 'relationId' => $this->cjmPlaceId, 'imported' => '0']);
        // UiTPASPlaceId also has the CJM label — same place, should not be an error
        $this->getConnection()->insert('labels_relations', ['labelName' => self::CJM_LABEL, 'relationType' => 'Place', 'relationId' => $this->UiTPASPlaceId, 'imported' => '0']);

        $this->getConnection()->insert('event_relations', ['event' => 'cb11d320-17dc-41f8-831a-8b9d8208ea80', 'organizer' => 'eb89d990-8f5b-46be-a548-c87a300a54c8', 'place' => $this->museumPassPlaceId]);
        $this->getConnection()->insert('event_relations', ['event' => '86e4540d-eed2-4cc5-8e08-a9f684deb03f', 'organizer' => 'eb89d990-8f5b-46be-a548-c87a300a54c8', 'place' => $this->museumPassPlaceId]);
        $this->getConnection()->insert('event_relations', ['event' => 'bf1ba6c5-6d02-4c08-ab62-84ce8aa214a0', 'organizer' => 'eb89d990-8f5b-46be-a548-c87a300a54c8', 'place' => $this->mostEventsPlaceId]);

        for ($i = 0; $i < 10; $i++) {
            $placeId = '4b4ca084-b78e-474f-b868-6f9df2d20df' . $i;

            $this->getConnection()->insert('duplicate_places', ['cluster_id' => 'cluster_4', 'place_uuid' => $placeId]);

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

        $this->canonicalService = new CanonicalServiceFromLabels(
            ['museumPASSmusees', self::UITPAS_LABEL, self::UITPAS_LABEL_GENT, self::CJM_LABEL],
            new DBALDuplicatePlaceRepository($this->getConnection()),
            new DBALEventRelationsRepository($this->getConnection()),
            new DBALReadRepository($this->getConnection(), 'labels_relations'),
            $documentRepository
        );
    }

    /**
     * @test
     */
    public function it_will_return_the_MPM_place(): void
    {
        $canonicalId = $this->canonicalService->getCanonical('cluster_1');

        $this->assertEquals($this->museumPassPlaceId, $canonicalId);
    }

    /**
     * @test
     */
    public function it_will_get_the_place_with_most_events(): void
    {
        $canonicalId = $this->canonicalService->getCanonical('cluster_3');

        $this->assertEquals($this->mostEventsPlaceId, $canonicalId);
    }

    /**
     * @test
     */
    public function it_will_throw_an_exception_when_cluster_contains_2_labeled_places(): void
    {
        $this->expectException(MultipleCanonicalPlacesInCluster::class);
        $this->expectExceptionMessage('Cluster cluster_2 contains 2 places with a canonical label: 526605d3-7cc4-4607-97a4-065896253f42, 901e23fe-b393-4cc6-9307-8e3e3f2ea77f');

        $this->canonicalService->getCanonical('cluster_2');
    }

    /**
     * @test
     */
    public function it_will_throw_an_exception_when_cluster_contains_2_canonical_labels(): void
    {
        $this->expectException(MultipleCanonicalPlacesInCluster::class);
        $this->expectExceptionMessage(sprintf('Cluster cluster_uitpas contains 2 places with a canonical label: %s, %s', $this->UiTPASPlaceId, $this->anotherUiTPASPlaceId));

        $this->canonicalService->getCanonical('cluster_uitpas');
    }

    /**
     * @test
     */
    public function it_will_throw_an_exception_when_cluster_contains_an_UITPAS_AND_MPM_place(): void
    {
        $this->expectException(MultipleCanonicalPlacesInCluster::class);
        $this->expectExceptionMessage(sprintf('Cluster cluster_mpm_and_uitpas contains 2 places with a canonical label: %s, %s', $this->museumPassPlaceId, $this->UiTPASPlaceId));

        $this->canonicalService->getCanonical('cluster_mpm_and_uitpas');
    }

    /**
     * @test
     */
    public function it_will_get_the_oldest_place_if_equal_nr_of_events(): void
    {
        $canonicalId = $this->canonicalService->getCanonical('cluster_4');

        $this->assertEquals($this->oldestPlaceId, $canonicalId);
    }

    /**
     * @test
     */
    public function it_will_get_uitpas_location(): void
    {
        $canonicalId = $this->canonicalService->getCanonical('cluster_with_one_uitpas_location');

        $this->assertEquals($this->UiTPASPlaceId, $canonicalId);
    }

    /**
     * @test
     */
    public function it_will_return_the_cjm_place(): void
    {
        $canonicalId = $this->canonicalService->getCanonical('cluster_cjm');

        $this->assertEquals($this->cjmPlaceId, $canonicalId);
    }

    /**
     * @test
     */
    public function it_will_throw_an_exception_when_cluster_contains_cjm_and_mpm_places(): void
    {
        $this->expectException(MultipleCanonicalPlacesInCluster::class);
        $this->expectExceptionMessage(sprintf('Cluster cluster_cjm_and_mpm contains 2 places with a canonical label: %s, %s', $this->cjmPlaceId, $this->museumPassPlaceId));

        $this->canonicalService->getCanonical('cluster_cjm_and_mpm');
    }

    /**
     * @test
     */
    public function it_will_not_throw_when_one_place_has_both_uitpas_and_cjm_labels(): void
    {
        // UiTPASPlaceId has both UITPAS_LABEL and CJM_LABEL — same place, should resolve without error
        $canonicalId = $this->canonicalService->getCanonical('cluster_uitpas_and_cjm_same_place');

        $this->assertEquals($this->UiTPASPlaceId, $canonicalId);
    }

    /**
     * @test
     */
    public function it_returns_mpm_place_from_array(): void
    {
        $canonicalId = $this->canonicalService->getCanonicalFromArrayWithoutThrowing([
            $this->museumPassPlaceId,
            $this->mostEventsPlaceId,
        ]);

        $this->assertEquals($this->museumPassPlaceId, $canonicalId);
    }

    /**
     * @test
     */
    public function it_returns_mpm_place_with_most_events_without_throwing_when_multiple_mpm_places(): void
    {
        // museumPassPlaceId has 2 events, anotherMuseumPassPlaceId has 0 events
        $canonicalId = $this->canonicalService->getCanonicalFromArrayWithoutThrowing([
            $this->anotherMuseumPassPlaceId,
            $this->museumPassPlaceId,
        ]);

        $this->assertEquals($this->museumPassPlaceId, $canonicalId);
    }

    /**
     * @test
     */
    public function it_returns_uitpas_place_from_array(): void
    {
        $canonicalId = $this->canonicalService->getCanonicalFromArrayWithoutThrowing([
            $this->UiTPASPlaceId,
            $this->oldestPlaceId,
        ]);

        $this->assertEquals($this->UiTPASPlaceId, $canonicalId);
    }

    /**
     * @test
     */
    public function it_returns_oldest_uitpas_labeled_place_when_labeled_places_have_tied_events(): void
    {
        $canonicalId = $this->canonicalService->getCanonicalFromArrayWithoutThrowing([
            $this->UiTPASPlaceId,
            $this->anotherUiTPASPlaceId,
        ]);

        $this->assertEquals($this->UiTPASPlaceId, $canonicalId);
    }

    /**
     * @test
     */
    public function it_returns_place_with_most_events_when_labeled_places_conflict(): void
    {
        // museumPassPlaceId has 2 events, UiTPASPlaceId has 0 events
        $canonicalId = $this->canonicalService->getCanonicalFromArrayWithoutThrowing([
            $this->museumPassPlaceId,
            $this->UiTPASPlaceId,
        ]);

        $this->assertEquals($this->museumPassPlaceId, $canonicalId);
    }

    /**
     * @test
     */
    public function it_returns_oldest_labeled_place_when_labeled_places_have_tied_events(): void
    {
        // anotherMuseumPassPlaceId (2021) and UiTPASPlaceId (2019) both have 0 events — UiTPASPlaceId is oldest
        $canonicalId = $this->canonicalService->getCanonicalFromArrayWithoutThrowing([
            $this->anotherMuseumPassPlaceId,
            $this->UiTPASPlaceId,
        ]);

        $this->assertEquals($this->UiTPASPlaceId, $canonicalId);
    }

    /**
     * @test
     */
    public function it_returns_place_with_most_events_from_array(): void
    {
        $canonicalId = $this->canonicalService->getCanonicalFromArrayWithoutThrowing([
            $this->mostEventsPlaceId,
            $this->oldestPlaceId,
        ]);

        $this->assertEquals($this->mostEventsPlaceId, $canonicalId);
    }

    /**
     * @test
     */
    public function it_returns_oldest_place_when_events_are_tied(): void
    {
        $canonicalId = $this->canonicalService->getCanonicalFromArrayWithoutThrowing([
            $this->oldestPlaceId,
            '4b4ca084-b78e-474f-b868-6f9df2d20df1',
        ]);

        $this->assertEquals($this->oldestPlaceId, $canonicalId);
    }
}
