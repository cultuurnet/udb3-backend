<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Canonical;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use CultuurNet\UDB3\Event\ReadModel\Relations\Doctrine\DBALRepository;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\Doctrine\DBALReadRepository;
use CultuurNet\UDB3\Place\Canonical\Exception\MuseumPassNotUniqueInCluster;
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

    private CanonicalService $canonicalService;

    private string $museumPassPlaceId;

    private string $anotherMuseumPassPlaceId;

    private string $mostEventsPlaceId;

    private string $oldestPlaceId;

    public function setUp(): void
    {
        $this->oldestPlaceId = '8717c43d-026f-42e9-9ea9-799623c5763c';
        $this->museumPassPlaceId = '901e23fe-b393-4cc6-9307-8e3e3f2ea77f';
        $this->anotherMuseumPassPlaceId = '526605d3-7cc4-4607-97a4-065896253f42';
        $this->mostEventsPlaceId = '34621f3b-b626-4672-be7c-33972ac13791';

        $table = new Table('duplicate_places');
        $table->addColumn('cluster_id', Type::BIGINT)->setNotnull(true);
        $table->addColumn('place_uuid', Type::GUID)->setLength(36)->setNotnull(true);
        $this->createTable($table);
        $this->getConnection()->insert(
            'duplicate_places',
            [
                'cluster_id' => '1',
                'place_uuid' => '5d202668-7b6f-4848-9271-f0e0474f7922',
            ]
        );
        $this->getConnection()->insert(
            'duplicate_places',
            [
                'cluster_id' => '1',
                'place_uuid' => $this->museumPassPlaceId,
            ]
        );
        $this->getConnection()->insert(
            'duplicate_places',
            [
                'cluster_id' => '1',
                'place_uuid' => 'b22d5d76-dceb-4583-8947-e1183a93c10d',
            ]
        );
        $this->getConnection()->insert(
            'duplicate_places',
            [
                'cluster_id' => '2',
                'place_uuid' => $this->anotherMuseumPassPlaceId,
            ]
        );
        $this->getConnection()->insert(
            'duplicate_places',
            [
                'cluster_id' => '2',
                'place_uuid' => $this->museumPassPlaceId,
            ]
        );
        $this->getConnection()->insert(
            'duplicate_places',
            [
                'cluster_id' => '2',
                'place_uuid' => '5d202668-7b6f-4848-9271-f0e0474f7922',
            ]
        );
        $this->getConnection()->insert(
            'duplicate_places',
            [
                'cluster_id' => '2',
                'place_uuid' => 'b22d5d76-dceb-4583-8947-e1183a93c10d',
            ]
        );
        $this->getConnection()->insert(
            'duplicate_places',
            [
                'cluster_id' => '3',
                'place_uuid' => '5d202668-7b6f-4848-9271-f0e0474f7922',
            ]
        );
        $this->getConnection()->insert(
            'duplicate_places',
            [
                'cluster_id' => '3',
                'place_uuid' => $this->mostEventsPlaceId,
            ]
        );
        $this->getConnection()->insert(
            'duplicate_places',
            [
                'cluster_id' => '3',
                'place_uuid' => 'b22d5d76-dceb-4583-8947-e1183a93c10d',
            ]
        );

        $this->getConnection()->insert(
            'duplicate_places',
            [
                'cluster_id' => '4',
                'place_uuid' => $this->oldestPlaceId,
            ]
        );

        $documentRepository = new InMemoryDocumentRepository();
        $labelsRelations = new Table('labels_relations');
        $labelsRelations->addColumn('labelName', Type::STRING)->setLength(255);
        $labelsRelations->addColumn('relationType', Type::STRING)->setLength(255);
        $labelsRelations->addColumn('relationId', Type::BIGINT)->setNotnull(true);
        $labelsRelations->addColumn('imported', Type::SMALLINT)->setNotnull(true)->setNotnull(0);
        $this->createTable($labelsRelations);

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
                'relationId'=> $this->anotherMuseumPassPlaceId,
                'imported' => '0',
            ]
        );

        $eventRelations = new Table('event_relations');
        $eventRelations->addColumn('event', Type::STRING)->setLength(36)->setNotnull(true);
        $eventRelations->addColumn('organizer', Type::STRING)->setLength(36)->setNotnull(true);
        $eventRelations->addColumn('place', Type::STRING)->setLength(36)->setNotnull(true);
        $this->createTable($eventRelations);

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

        $this->duplicatePlaceRepository = new DBALDuplicatePlaceRepository($this->getConnection());

        for ($i = 0; $i < 10; $i++) {
            $placeId = '4b4ca084-b78e-474f-b868-6f9df2d20df' . $i;

            $this->getConnection()->insert(
                'duplicate_places',
                [
                    'cluster_id' => '4',
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
            new DBALDuplicatePlaceRepository($this->getConnection()),
            new DBALRepository(
                $this->getConnection()
            ),
            new DBALReadRepository(
                $this->getConnection(),
                new StringLiteral('labels_relations')
            ),
            $documentRepository
        );
    }

    /**
     * @test
     */
    public function it_will_return_the_MPM_place(): void
    {
        $canonicalSet = $this->canonicalService->getCanonicalSet(1);

        $this->assertEquals(
            [$this->museumPassPlaceId => [
                '5d202668-7b6f-4848-9271-f0e0474f7922',
                'b22d5d76-dceb-4583-8947-e1183a93c10d',
            ],
            ],
            $canonicalSet
        );
    }

    /**
     * @test
     */
    public function it_will_get_the_place_with_most_events(): void
    {
        $canonicalId = $this->canonicalService->getCanonicalSet(3);

        $this->assertEquals([$this->mostEventsPlaceId =>
        [
            '5d202668-7b6f-4848-9271-f0e0474f7922',
            'b22d5d76-dceb-4583-8947-e1183a93c10d',
        ],

            ], $canonicalId);
    }

    /**
     * @test
     */
    public function it_will_throw_an_exception_when_cluster_contains_2_MPM_places(): void
    {
        $this->expectException(MuseumPassNotUniqueInCluster::class);
        $this->expectExceptionMessage('Cluster 2 contains 2 MuseumPass places');

        $this->canonicalService->getCanonicalSet(2);
    }

    /**
     * @test
     */
    public function it_will_get_the_oldest_place_if_equal_nr_of_events(): void
    {
        $cluster = [
            '4b4ca084-b78e-474f-b868-6f9df2d20df0',
            $this->oldestPlaceId,
            '4b4ca084-b78e-474f-b868-6f9df2d20df1',
            '4b4ca084-b78e-474f-b868-6f9df2d20df2',
            '4b4ca084-b78e-474f-b868-6f9df2d20df3',
            '4b4ca084-b78e-474f-b868-6f9df2d20df4',
            '4b4ca084-b78e-474f-b868-6f9df2d20df5',
            '4b4ca084-b78e-474f-b868-6f9df2d20df6',
            '4b4ca084-b78e-474f-b868-6f9df2d20df7',
            '4b4ca084-b78e-474f-b868-6f9df2d20df8',
            '4b4ca084-b78e-474f-b868-6f9df2d20df9',
        ];

        $canonicalId = $this->canonicalService->getCanonicalSet(4);

        $this->assertEquals([
            $this->oldestPlaceId => [
                '4b4ca084-b78e-474f-b868-6f9df2d20df0',
                '4b4ca084-b78e-474f-b868-6f9df2d20df1',
                '4b4ca084-b78e-474f-b868-6f9df2d20df2',
                '4b4ca084-b78e-474f-b868-6f9df2d20df3',
                '4b4ca084-b78e-474f-b868-6f9df2d20df4',
                '4b4ca084-b78e-474f-b868-6f9df2d20df5',
                '4b4ca084-b78e-474f-b868-6f9df2d20df6',
                '4b4ca084-b78e-474f-b868-6f9df2d20df7',
                '4b4ca084-b78e-474f-b868-6f9df2d20df8',
                '4b4ca084-b78e-474f-b868-6f9df2d20df9',
            ],
        ], $canonicalId);
    }
}
