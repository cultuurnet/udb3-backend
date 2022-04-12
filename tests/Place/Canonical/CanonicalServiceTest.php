<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Canonical;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use CultuurNet\UDB3\Event\ReadModel\Relations\Doctrine\DBALRepository;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\Doctrine\DBALReadRepository;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\StringLiteral;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\TestCase;

class CanonicalServiceTest extends TestCase
{
    use DBALTestConnectionTrait;

    private DBALDuplicatePlaceRepository $duplicatePlaceRepository;

    private DocumentRepository $documentRepository;

    private CanonicalService $canonicalService;

    public function setUp(): void
    {
        $this->documentRepository = new InMemoryDocumentRepository();
        $labels_relations = new Table('labels_relations');
        $labels_relations->addColumn('labelName', Type::STRING)->setLength(255);
        $labels_relations->addColumn('relationType', Type::STRING)->setLength(255);
        $labels_relations->addColumn('relationId', Type::BIGINT)->setNotnull(true);
        $labels_relations->addColumn('imported', Type::SMALLINT)->setNotnull(true)->setNotnull(0);
        $this->createTable($labels_relations);

        $this->getConnection()->insert(
            'labels_relations',
            [
                'labelName' => 'museumPASSmusees',
                'relationType' => 'Place',
                'relationId'=> '526605d3-7cc4-4607-97a4-065896253f42',
                'imported' => '0',
            ]
        );

        $this->getConnection()->insert(
            'labels_relations',
            [
                'labelName' => 'museumPASSmusees',
                'relationType' => 'Place',
                'relationId'=> '901e23fe-b393-4cc6-9307-8e3e3f2ea77f',
                'imported' => '0',
            ]
        );

        $event_relations = new Table('event_relations');
        $event_relations->addColumn('event', Type::STRING)->setLength(36)->setNotnull(true);
        $event_relations->addColumn('organizer', Type::STRING)->setLength(36)->setNotnull(true);
        $event_relations->addColumn('place', Type::STRING)->setLength(36)->setNotnull(true);
        $this->createTable($event_relations);

        $this->getConnection()->insert(
            'event_relations',
            [
                'event' => 'cb11d320-17dc-41f8-831a-8b9d8208ea80',
                'organizer' => 'eb89d990-8f5b-46be-a548-c87a300a54c8',
                'place'=> '901e23fe-b393-4cc6-9307-8e3e3f2ea77f',
            ]
        );
        $this->getConnection()->insert(
            'event_relations',
            [
                'event' => '86e4540d-eed2-4cc5-8e08-a9f684deb03f',
                'organizer' => 'eb89d990-8f5b-46be-a548-c87a300a54c8',
                'place'=> '901e23fe-b393-4cc6-9307-8e3e3f2ea77f',
            ]
        );
        $this->getConnection()->insert(
            'event_relations',
            [
                'event' => 'bf1ba6c5-6d02-4c08-ab62-84ce8aa214a0',
                'organizer' => 'eb89d990-8f5b-46be-a548-c87a300a54c8',
                'place'=> '526605d3-7cc4-4607-97a4-065896253f42',
            ]
        );

        $this->duplicatePlaceRepository = new DBALDuplicatePlaceRepository($this->getConnection());

        $oldestPlaceId = '8717c43d-026f-42e9-9ea9-799623c5763c';
        $oldestJsonDocument = new JsonDocument(
            $oldestPlaceId,
            json_encode((object) ['@id' => $oldestPlaceId, 'created' => '2017-12-09T19:40:58+00:00'])
        );
        $this->documentRepository->save($oldestJsonDocument);

        $middlePlaceId = '4b4ca084-b78e-474f-b868-6f9df2d20df7';
        $middleJsonDocument = new JsonDocument(
            $middlePlaceId,
            json_encode((object) ['@id' => $middlePlaceId, 'created' => '2019-12-09T19:40:58+00:00'])
        );
        $this->documentRepository->save($middleJsonDocument);

        $newestPlaceId = '9c3ed0a7-b0e6-4e8d-996f-786231d31816';
        $newestJsonDocument = new JsonDocument(
            $newestPlaceId,
            json_encode((object) ['@id' => $newestPlaceId, 'created' => '2021-12-09T19:40:58+00:00'])
        );
        $this->documentRepository->save($newestJsonDocument);

        $this->canonicalService = new CanonicalService(
            'museumPASSmusees',
            new DBALRepository(
                $this->getConnection()
            ),
            new DBALReadRepository(
                $this->getConnection(),
                new StringLiteral('labels_relations')
            ),
            $this->documentRepository
        );
    }

    /**
     * @test
     */
    public function it_will_return_the_MPM_place(): void
    {
        $canonicalId = $this->canonicalService->getCanonical(
            [
                '5d202668-7b6f-4848-9271-f0e0474f7922',
                'b22d5d76-dceb-4583-8947-e1183a93c10d',
                '901e23fe-b393-4cc6-9307-8e3e3f2ea77f',
            ]
        );

        $this->assertEquals('901e23fe-b393-4cc6-9307-8e3e3f2ea77f', $canonicalId);
    }

    /**
     * @test
     */
    public function it_will_get_the_place_with_the_most_events_when_cluster_contains_2_MPM_places(): void
    {
        $canonicalId = $this->canonicalService->getCanonical(
            [
                '5d202668-7b6f-4848-9271-f0e0474f7922',
                'b22d5d76-dceb-4583-8947-e1183a93c10d',
                '526605d3-7cc4-4607-97a4-065896253f42',
                '901e23fe-b393-4cc6-9307-8e3e3f2ea77f',
            ]
        );

        $this->assertEquals('901e23fe-b393-4cc6-9307-8e3e3f2ea77f', $canonicalId);
    }

    /**
     * @test
     */
    public function it_will_get_the_oldest_place_if_equel_nr_of_events(): void
    {
        $canonicalId = $this->canonicalService->getCanonical(
            [
                '8717c43d-026f-42e9-9ea9-799623c5763c',
                '4b4ca084-b78e-474f-b868-6f9df2d20df7',
                '9c3ed0a7-b0e6-4e8d-996f-786231d31816',
            ]
        );

        $this->assertEquals('8717c43d-026f-42e9-9ea9-799623c5763c', $canonicalId);
    }
}
