<?php

namespace CultuurNet\UDB3\EventSourcing\DBAL;

use Broadway\Domain\DateTime as BroadwayDateTime;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventStore\EventStreamNotFoundException;
use Broadway\Serializer\SerializerInterface;
use Broadway\Serializer\SimpleInterfaceSerializer;
use CultuurNet\UDB3\DBALTestConnectionTrait;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;

class AggregateAwareDBALEventStoreTest extends TestCase
{
    use DBALTestConnectionTrait;

    /**
     * @var AggregateAwareDBALEventStore
     */
    private $aggregateAwareDBALEventStore;

    /**
     * @var SerializerInterface
     */
    private $payloadSerializer;

    /**
     * @var SerializerInterface
     */
    private $metadataSerializer;

    /**
     * @var string
     */
    private $tableName;

    /**
     * @var string
     */
    private $aggregateType;

    protected function setUp()
    {
        $this->payloadSerializer = new SimpleInterfaceSerializer();

        $this->metadataSerializer = new SimpleInterfaceSerializer();

        $this->tableName = 'event_store';

        $this->aggregateType = 'place';

        $this->aggregateAwareDBALEventStore = new AggregateAwareDBALEventStore(
            $this->getConnection(),
            $this->payloadSerializer,
            $this->metadataSerializer,
            $this->tableName,
            $this->aggregateType
        );

        $this->createTable();
    }

    /**
     * @test
     */
    public function it_can_load_an_aggregate_by_its_id()
    {
        $uuid = new UUID();
        $domainMessage = $this->createDomainMessage($uuid);

        $this->insertDomainMessage($domainMessage);

        $domainEventStream = $this->aggregateAwareDBALEventStore->load(
            $uuid->toNative()
        );

        $this->assertEquals(
            new DomainEventStream([$domainMessage]),
            $domainEventStream
        );
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_loading_a_non_existing_aggregate()
    {
        $uuid = new UUID();

        $this->expectException(EventStreamNotFoundException::class);
        $this->expectExceptionMessage(sprintf(
            'EventStream not found for aggregate with id %s',
            $uuid->toNative()
        ));

        $this->aggregateAwareDBALEventStore->load($uuid->toNative());
    }

    /**
     * @test
     */
    public function it_can_append_to_an_aggregate()
    {
        $uuid = new UUID();
        $domainMessage = $this->createDomainMessage($uuid);

        $this->aggregateAwareDBALEventStore->append(
            $uuid->toNative(),
            new DomainEventStream([$domainMessage])
        );

        $rows = $this->selectDomainMessage($uuid);

        $this->assertCount(1, $rows);
        $this->assertEquals(
            $this->domainMessageToRow($domainMessage),
            $rows[0]
        );
    }

    private function createTable()
    {
        $schemaManager = $this->getConnection()->getSchemaManager();
        $schema = $schemaManager->createSchema();

        $table = $this->aggregateAwareDBALEventStore->configureSchema(
            $schema
        );

        $schemaManager->createTable($table);
    }

    /**
     * @param UUID $uuid
     * @return DomainMessage
     */
    private function createDomainMessage(UUID $uuid)
    {
        return new DomainMessage(
            $uuid->toNative(),
            0,
            new Metadata([
                'meta' => 'some meta',
            ]),
            new DummyEvent(
                $uuid->toNative(),
                'i am content = ik ben tevreden'
            ),
            BroadwayDateTime::now()
        );
    }


    /**
     * @param DomainMessage $domainMessage
     * @return array
     */
    private function domainMessageToRow(DomainMessage $domainMessage)
    {
        return [
           'uuid' => $domainMessage->getId(),
           'playhead' => $domainMessage->getPlayhead(),
           'metadata' => json_encode($this->metadataSerializer->serialize($domainMessage->getMetadata())),
           'payload' => json_encode($this->payloadSerializer->serialize($domainMessage->getPayload())),
           'recorded_on' => $domainMessage->getRecordedOn()->toString(),
           'type' => $domainMessage->getType(),
           'aggregate_type' => $this->aggregateType,
        ];
    }

    /**
     * @param DomainMessage $domainMessage
     */
    private function insertDomainMessage(DomainMessage $domainMessage)
    {
        $this->connection->insert(
            $this->tableName,
            $this->domainMessageToRow($domainMessage)
        );
    }

    /**
     * @param UUID $uuid
     * @return array
     */
    private function selectDomainMessage(UUID $uuid)
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
            ->setParameter(':uuid', $uuid->toNative());

        $statement = $queryBuilder->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }
}
