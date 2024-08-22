<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventSourcing\DBAL;

use Broadway\Domain\DateTime as BroadwayDateTime;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventStore\EventStreamNotFoundException;
use Broadway\Serializer\Serializer;
use Broadway\Serializer\SimpleInterfaceSerializer;
use CultuurNet\UDB3\DBALTestConnectionTrait;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\AggregateType;
use PHPUnit\Framework\TestCase;

class AggregateAwareDBALEventStoreTest extends TestCase
{
    use DBALTestConnectionTrait;

    private AggregateAwareDBALEventStore $aggregateAwareDBALEventStore;

    private Serializer $payloadSerializer;

    private Serializer $metadataSerializer;

    private string $tableName;

    private AggregateType $aggregateType;

    protected function setUp(): void
    {
        $this->setUpDatabase();

        $this->payloadSerializer = new SimpleInterfaceSerializer();

        $this->metadataSerializer = new SimpleInterfaceSerializer();

        $this->tableName = 'event_store';

        $this->aggregateType = AggregateType::place();

        $this->aggregateAwareDBALEventStore = new AggregateAwareDBALEventStore(
            $this->getConnection(),
            $this->payloadSerializer,
            $this->metadataSerializer,
            $this->tableName,
            $this->aggregateType
        );
    }

    /**
     * @test
     */
    public function it_can_load_an_aggregate_by_its_id(): void
    {
        $uuid = new UUID('072bb7b7-b58e-4f1a-a22e-399852e107a0');
        $domainMessage = $this->createDomainMessage($uuid);

        $this->insertDomainMessage($domainMessage);

        $domainEventStream = $this->aggregateAwareDBALEventStore->load(
            $uuid->toString()
        );

        $this->assertEquals(
            new DomainEventStream([$domainMessage]),
            $domainEventStream
        );
    }

    /**
     * @test
     */
    public function it_can_load_an_aggregate_by_its_id_and_from_a_playhead(): void
    {
        $uuid = new UUID('f905f1d8-e156-487a-8dc8-b4dde09c760d');
        $domainMessages = $this->createDomainMessages($uuid);

        foreach ($domainMessages as $domainMessage) {
            $this->insertDomainMessage($domainMessage);
        }

        $domainEventStream = $this->aggregateAwareDBALEventStore->loadFromPlayhead(
            $uuid->toString(),
            1
        );

        $this->assertEquals(
            new DomainEventStream([$domainMessages[1], $domainMessages[2]]),
            $domainEventStream
        );
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_loading_a_non_existing_aggregate(): void
    {
        $uuid = new UUID('8f6b6aeb-4646-4b3a-90be-47b32593efea');

        $this->expectException(EventStreamNotFoundException::class);
        $this->expectExceptionMessage(sprintf(
            'EventStream not found for aggregate with id %s',
            $uuid->toString()
        ));

        $this->aggregateAwareDBALEventStore->load($uuid->toString());
    }

    /**
     * @test
     */
    public function it_can_append_to_an_aggregate(): void
    {
        $uuid = new UUID('ab681b82-a014-4c2a-a271-0f3695c1b4f2');
        $domainMessage = $this->createDomainMessage($uuid);

        $this->aggregateAwareDBALEventStore->append(
            $uuid->toString(),
            new DomainEventStream([$domainMessage])
        );

        $rows = $this->selectDomainMessage($uuid);

        $this->assertCount(1, $rows);
        $this->assertEquals(
            $this->domainMessageToRow($domainMessage),
            $rows[0]
        );
    }

    private function createTable(): void
    {
        $schemaManager = $this->getConnection()->getSchemaManager();
        $schema = $schemaManager->createSchema();

        $table = $this->aggregateAwareDBALEventStore->configureSchema(
            $schema
        );

        $schemaManager->createTable($table);
    }

    private function createDomainMessage(UUID $uuid): DomainMessage
    {
        return new DomainMessage(
            $uuid->toString(),
            0,
            new Metadata([
                'meta' => 'some meta',
            ]),
            new DummyEvent(
                $uuid->toString(),
                'i am content = ik ben tevreden'
            ),
            BroadwayDateTime::now()
        );
    }

    /**
     * @return DomainMessage[]
     */
    private function createDomainMessages(UUID $uuid): array
    {
        return [
            new DomainMessage(
                $uuid->toString(),
                0,
                new Metadata([
                    'meta' => 'meta 0',
                ]),
                new DummyEvent(
                    $uuid->toString(),
                    'event 0'
                ),
                BroadwayDateTime::now()
            ),
            new DomainMessage(
                $uuid->toString(),
                1,
                new Metadata([
                    'meta' => 'meta 1',
                ]),
                new DummyEvent(
                    $uuid->toString(),
                    'event 1'
                ),
                BroadwayDateTime::now()
            ),
            new DomainMessage(
                $uuid->toString(),
                2,
                new Metadata([
                    'meta' => 'meta 2',
                ]),
                new DummyEvent(
                    $uuid->toString(),
                    'event 2'
                ),
                BroadwayDateTime::now()
            ),
        ];
    }

    private function domainMessageToRow(DomainMessage $domainMessage): array
    {
        return [
           'uuid' => $domainMessage->getId(),
           'playhead' => $domainMessage->getPlayhead(),
           'metadata' => Json::encode($this->metadataSerializer->serialize($domainMessage->getMetadata())),
           'payload' => Json::encode($this->payloadSerializer->serialize($domainMessage->getPayload())),
           'recorded_on' => $domainMessage->getRecordedOn()->toString(),
           'type' => $domainMessage->getType(),
           'aggregate_type' => $this->aggregateType->toString(),
        ];
    }

    private function insertDomainMessage(DomainMessage $domainMessage): void
    {
        $this->connection->insert(
            $this->tableName,
            $this->domainMessageToRow($domainMessage)
        );
    }

    private function selectDomainMessage(UUID $uuid): array
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder->select(
            [
                'uuid',
                'playhead',
                'metadata',
                'payload',
                'recorded_on',
                'type',
                'aggregate_type',
            ]
        )
            ->from($this->tableName)
            ->where('uuid = :uuid')
            ->setParameter(':uuid', $uuid->toString());

        return $queryBuilder->execute()->fetchAllAssociative();
    }
}
