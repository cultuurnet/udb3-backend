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
use CultuurNet\UDB3\Silex\AggregateType;
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
     * @var Serializer
     */
    private $payloadSerializer;

    /**
     * @var Serializer
     */
    private $metadataSerializer;

    /**
     * @var string
     */
    private $tableName;

    /**
     * @var AggregateType
     */
    private $aggregateType;

    protected function setUp()
    {
        $this->payloadSerializer = new SimpleInterfaceSerializer();

        $this->metadataSerializer = new SimpleInterfaceSerializer();

        $this->tableName = 'event_store';

        $this->aggregateType = AggregateType::PLACE();

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
    public function it_can_load_an_aggregate_by_its_id_and_from_a_playhead()
    {
        $uuid = new UUID();
        $domainMessages = $this->createDomainMessages($uuid);

        foreach ($domainMessages as $domainMessage) {
            $this->insertDomainMessage($domainMessage);
        }

        $domainEventStream = $this->aggregateAwareDBALEventStore->loadFromPlayhead(
            $uuid->toNative(),
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
     * @return DomainMessage[]
     */
    private function createDomainMessages(UUID $uuid)
    {
        return [
            new DomainMessage(
                $uuid->toNative(),
                0,
                new Metadata([
                    'meta' => 'meta 0',
                ]),
                new DummyEvent(
                    $uuid->toNative(),
                    'event 0'
                ),
                BroadwayDateTime::now()
            ),
            new DomainMessage(
                $uuid->toNative(),
                1,
                new Metadata([
                    'meta' => 'meta 1',
                ]),
                new DummyEvent(
                    $uuid->toNative(),
                    'event 1'
                ),
                BroadwayDateTime::now()
            ),
            new DomainMessage(
                $uuid->toNative(),
                2,
                new Metadata([
                    'meta' => 'meta 2',
                ]),
                new DummyEvent(
                    $uuid->toNative(),
                    'event 2'
                ),
                BroadwayDateTime::now()
            ),
        ];
    }

    /**
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


    private function insertDomainMessage(DomainMessage $domainMessage)
    {
        $this->connection->insert(
            $this->tableName,
            $this->domainMessageToRow($domainMessage)
        );
    }

    /**
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
