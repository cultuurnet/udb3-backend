<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventSourcing\DBAL;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventSourcing\EventStreamDecorator;
use Broadway\EventStore\EventStore;
use Broadway\Serializer\SimpleInterfaceSerializer;
use CultuurNet\UDB3\AggregateType;
use CultuurNet\UDB3\DBALTestConnectionTrait;
use PHPUnit\Framework\TestCase;

class EventStreamTest extends TestCase
{
    use DBALTestConnectionTrait;

    private AggregateAwareDBALEventStore $eventStore;

    private EventStream $eventStream;

    public function setUp(): void
    {
        $this->setUpDatabase();

        $table = 'event_store';
        $payloadSerializer = new SimpleInterfaceSerializer();
        $metadataSerializer = new SimpleInterfaceSerializer();

        $this->eventStore = new AggregateAwareDBALEventStore(
            $this->getConnection(),
            $payloadSerializer,
            $metadataSerializer,
            $table,
            AggregateType::event()
        );

        $this->eventStream = new EventStream(
            $this->getConnection(),
            $payloadSerializer,
            $metadataSerializer,
            $table
        );
    }

    /**
     * @test
     * @dataProvider invalidStartIdDataProvider
     */
    public function it_requires_a_value_higher_than_zero_for_optional_start_id(int $invalidStartId): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('StartId should be higher than 0.');

        $this->eventStream->withStartId($invalidStartId);
    }

    public function invalidStartIdDataProvider(): array
    {
        return [
            [0],
            [-1],
            [-0],
        ];
    }

    /**
     * @test
     */
    public function it_requires_non_empty_value_for_optional_cdbid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cdbids can\'t be empty.');

        $this->eventStream->withCdbids([]);
    }

    /**
     * @test
     * @dataProvider eventStreamDecoratorDataProvider
     */
    public function it_retrieves_all_events_from_the_event_store(
        EventStreamDecorator $eventStreamDecorator = null,
        array $expectedDecoratedMetadata = []
    ): void {
        $history = $this->fillHistory();

        if (!is_null($eventStreamDecorator)) {
            $eventStream = $this->eventStream
                ->withDomainEventStreamDecorator($eventStreamDecorator);
        } else {
            $eventStream = $this->eventStream;
        }

        $domainEventStreams = $eventStream();

        $domainEventStreams = iterator_to_array($domainEventStreams);

        $expectedDomainEventStreams = [];

        foreach ($history as $key => $domainMessage) {
            $expectedDomainMessage = $domainMessage->andMetadata($expectedDecoratedMetadata[$key]);
            $expectedDomainEventStreams[] = new DomainEventStream([$expectedDomainMessage]);
        }

        $this->assertEquals(
            $expectedDomainEventStreams,
            $domainEventStreams
        );
    }

    public function eventStreamDecoratorDataProvider(): array
    {
        return [
            // No event stream decorator should result in no extra metadata.
            [
                null,
                [
                    0 => new Metadata(),
                    1 => new Metadata(),
                    2 => new Metadata(),
                    3 => new Metadata(),
                    4 => new Metadata(),
                    5 => new Metadata(),
                    6 => new Metadata(),
                    7 => new Metadata(),
                    8 => new Metadata(),
                ],
            ],
            // The dummy event stream decorator should add some extra mock
            // metadata.
            [
                new DummyEventStreamDecorator(),
                [
                    0 => new Metadata(
                        ['mock' => 'CultuurNet\UDB3\EventSourcing\DBAL\DummyEvent::F68E71A1-DBB0-4542-AEE5-BD937E095F74']
                    ),
                    1 => new Metadata(
                        ['mock' => 'CultuurNet\UDB3\EventSourcing\DBAL\DummyEvent::F68E71A1-DBB0-4542-AEE5-BD937E095F74']
                    ),
                    2 => new Metadata(
                        ['mock' => 'CultuurNet\UDB3\EventSourcing\DBAL\DummyEvent::011A02C5-D395-47C1-BEBE-184840A2C961']
                    ),
                    3 => new Metadata(
                        ['mock' => 'CultuurNet\UDB3\EventSourcing\DBAL\DummyEvent::9B994B6A-FE49-42B0-B67D-F681BE533A7A']
                    ),
                    4 => new Metadata(
                        ['mock' => 'CultuurNet\UDB3\EventSourcing\DBAL\DummyEvent::F68E71A1-DBB0-4542-AEE5-BD937E095F74']
                    ),
                    5 => new Metadata(
                        ['mock' => 'CultuurNet\UDB3\EventSourcing\DBAL\DummyEvent::F68E71A1-DBB0-4542-AEE5-BD937E095F74']
                    ),
                    6 => new Metadata(
                        ['mock' => 'CultuurNet\UDB3\EventSourcing\DBAL\DummyEvent::F68E71A1-DBB0-4542-AEE5-BD937E095F74']
                    ),
                    7 => new Metadata(
                        ['mock' => 'CultuurNet\UDB3\EventSourcing\DBAL\DummyEvent::F68E71A1-DBB0-4542-AEE5-BD937E095F74']
                    ),
                    8 => new Metadata(
                        ['mock' => 'CultuurNet\UDB3\EventSourcing\DBAL\DummyEvent::F68E71A1-DBB0-4542-AEE5-BD937E095F74']
                    ),
                ],
            ],
        ];
    }

    /**
     * @test
     */
    public function it_handles_a_specific_cdbid(): void
    {
        $cdbid = '9B994B6A-FE49-42B0-B67D-F681BE533A7A';
        $cdbids = [$cdbid];
        $history = $this->fillHistory();

        $eventStream = $this->eventStream->withCdbids($cdbids);

        $domainEventStreams = $eventStream();
        $domainEventStreams = iterator_to_array($domainEventStreams);
        $expectedDomainEventStreams = [];
        foreach ($history as $key => $domainMessage) {
            if ($domainMessage->getId() == $cdbid) {
                $expectedDomainEventStreams[] = new DomainEventStream(
                    [
                        $domainMessage,
                    ]
                );
            }
        }

        $this->assertEquals(
            $expectedDomainEventStreams,
            $domainEventStreams
        );
    }

    /**
     * @test
     */
    public function it_handles_a_start_id(): void
    {
        $history = $this->fillHistory();
        $eventStream = $this->eventStream->withStartId(4);

        /** @var EventStream|\Generator $domainEventStreams */
        $domainEventStreams = $eventStream();

        $domainEventStreams = iterator_to_array($domainEventStreams);
        $expectedDomainEventStreams = [];
        foreach ($history as $domainMessage) {
            $expectedDomainEventStreams[] = new DomainEventStream(
                [
                    $domainMessage,
                ]
            );
        }

        $this->assertEquals($expectedDomainEventStreams, $domainEventStreams);
    }

    /**
     * @test
     */
    public function it_returns_the_last_processed_id(): void
    {
        $this->fillHistory();

        $eventStream = $this->eventStream;
        $domainEventStreams = $eventStream();

        $expectedLastProcessedId = 1;
        while ($domainEventStreams->current()) {
            $this->assertEquals(
                $expectedLastProcessedId++,
                $eventStream->getLastProcessedId()
            );
            $domainEventStreams->next();
        }
    }

    /**
     * @return DomainMessage[]
     */
    private function fillHistory(): array
    {
        $idOfEntityA = 'F68E71A1-DBB0-4542-AEE5-BD937E095F74';
        $idOfEntityB = '011A02C5-D395-47C1-BEBE-184840A2C961';
        $idOfEntityC = '9B994B6A-FE49-42B0-B67D-F681BE533A7A';

        $history = [
            0 => new DomainMessage(
                'F68E71A1-DBB0-4542-AEE5-BD937E095F74',
                1,
                new Metadata(),
                new DummyEvent(
                    'F68E71A1-DBB0-4542-AEE5-BD937E095F74',
                    'test 123'
                ),
                DateTime::fromString('2015-01-02T08:30:00+0100')
            ),
            1 => new DomainMessage(
                'F68E71A1-DBB0-4542-AEE5-BD937E095F74',
                2,
                new Metadata(),
                new DummyEvent(
                    'F68E71A1-DBB0-4542-AEE5-BD937E095F74',
                    'test 123 456'
                ),
                DateTime::fromString('2015-01-02T08:40:00+0100')
            ),
            2 => new DomainMessage(
                $idOfEntityB,
                1,
                new Metadata(),
                new DummyEvent(
                    $idOfEntityB,
                    'entity b test content'
                ),
                DateTime::fromString('2015-01-02T08:41:00+0100')
            ),
            3 => new DomainMessage(
                $idOfEntityC,
                1,
                new Metadata(),
                new DummyEvent(
                    $idOfEntityC,
                    'entity c test content'
                ),
                DateTime::fromString('2015-01-02T08:42:30+0100')
            ),
            4 => new DomainMessage(
                $idOfEntityA,
                3,
                new Metadata(),
                new DummyEvent(
                    $idOfEntityA,
                    'entity a test content'
                ),
                DateTime::fromString('2015-01-03T16:00:01+0100')
            ),
            5 => new DomainMessage(
                $idOfEntityA,
                4,
                new Metadata(),
                new DummyEvent(
                    $idOfEntityA,
                    'entity a test content playhead 4'
                ),
                DateTime::fromString('2015-01-03T17:00:01+0100')
            ),
            6 => new DomainMessage(
                $idOfEntityA,
                5,
                new Metadata(),
                new DummyEvent(
                    $idOfEntityA,
                    'entity a test content playhead 5'
                ),
                DateTime::fromString('2015-01-03T18:00:01+0100')
            ),
            7 => new DomainMessage(
                $idOfEntityA,
                6,
                new Metadata(),
                new DummyEvent(
                    $idOfEntityA,
                    'entity a test content playhead 6'
                ),
                DateTime::fromString('2015-01-03T18:30:01+0100')
            ),
            8 => new DomainMessage(
                $idOfEntityA,
                7,
                new Metadata(),
                new DummyEvent(
                    $idOfEntityA,
                    'entity a test content playhead 7'
                ),
                DateTime::fromString('2015-01-03T19:45:00+0100')
            ),
        ];

        foreach ($history as $domainMessage) {
            $this->eventStore->append(
                $domainMessage->getId(),
                new DomainEventStream([$domainMessage])
            );
        }

        return $history;
    }

    /**
     * @test
     */
    public function it_can_handle_an_aggregate_type(): void
    {
        /** @var AggregateType[] $aggregateTypes */
        $aggregateTypes = [
            AggregateType::event(),
            AggregateType::place(),
            AggregateType::organizer(),
        ];

        $stores = [];
        foreach ($aggregateTypes as $aggregateType) {
            $stores[$aggregateType->toString()] = $this->createAggregateAwareDBALEventStore($aggregateType);
        }

        $domainMessages = $this->createDomainMessages();
        foreach ($domainMessages as $domainMessage) {
            $metadataAsArray = $domainMessage->getMetadata()->serialize();
            $eventStore = $stores[$metadataAsArray['aggregate_type']];
            $eventStore->append(
                $domainMessage->getId(),
                new DomainEventStream([$domainMessage])
            );
        }

        $eventStream = new EventStream(
            $this->getConnection(),
            new SimpleInterfaceSerializer(),
            new SimpleInterfaceSerializer(),
            'event_store'
        );

        foreach ($aggregateTypes as $aggregateType) {
            $this->checkEventStream($eventStream, $domainMessages, $aggregateType);
        }
    }

    /**
     * @return DomainMessage[]
     */
    private function createDomainMessages(): array
    {
        $idOfEntityA = 'F68E71A1-DBB0-4542-AEE5-BD937E095F74';
        $idOfEntityB = '011A02C5-D395-47C1-BEBE-184840A2C961';
        $idOfEntityC = '9B994B6A-FE49-42B0-B67D-F681BE533A7A';

        return [
            0 => new DomainMessage(
                $idOfEntityA,
                1,
                new Metadata(['aggregate_type' => 'event']),
                new DummyEvent(
                    $idOfEntityA,
                    'test 123'
                ),
                DateTime::fromString('2015-01-02T08:30:00+0100')
            ),
            1 => new DomainMessage(
                $idOfEntityA,
                2,
                new Metadata(['aggregate_type' => 'event']),
                new DummyEvent(
                    $idOfEntityA,
                    'test 123 456'
                ),
                DateTime::fromString('2015-01-02T08:40:00+0100')
            ),
            2 => new DomainMessage(
                $idOfEntityB,
                1,
                new Metadata(['aggregate_type' => 'place']),
                new DummyEvent(
                    $idOfEntityB,
                    'entity b test content'
                ),
                DateTime::fromString('2015-01-02T08:41:00+0100')
            ),
            3 => new DomainMessage(
                $idOfEntityC,
                1,
                new Metadata(['aggregate_type' => 'organizer']),
                new DummyEvent(
                    $idOfEntityC,
                    'entity c test content'
                ),
                DateTime::fromString('2015-01-02T08:42:30+0100')
            ),
            4 => new DomainMessage(
                $idOfEntityA,
                3,
                new Metadata(['aggregate_type' => 'event']),
                new DummyEvent(
                    $idOfEntityA,
                    'entity a test content'
                ),
                DateTime::fromString('2015-01-03T16:00:01+0100')
            ),
            5 => new DomainMessage(
                $idOfEntityA,
                4,
                new Metadata(['aggregate_type' => 'event']),
                new DummyEvent(
                    $idOfEntityA,
                    'entity a test content playhead 4'
                ),
                DateTime::fromString('2015-01-03T17:00:01+0100')
            ),
            6 => new DomainMessage(
                $idOfEntityA,
                5,
                new Metadata(['aggregate_type' => 'event']),
                new DummyEvent(
                    $idOfEntityA,
                    'entity a test content playhead 5'
                ),
                DateTime::fromString('2015-01-03T18:00:01+0100')
            ),
            7 => new DomainMessage(
                $idOfEntityA,
                6,
                new Metadata(['aggregate_type' => 'event']),
                new DummyEvent(
                    $idOfEntityA,
                    'entity a test content playhead 6'
                ),
                DateTime::fromString('2015-01-03T18:30:01+0100')
            ),
            8 => new DomainMessage(
                $idOfEntityA,
                7,
                new Metadata(['aggregate_type' => 'event']),
                new DummyEvent(
                    $idOfEntityA,
                    'entity a test content playhead 7'
                ),
                DateTime::fromString('2015-01-03T19:45:00+0100')
            ),
        ];
    }

    private function appendDomainMessages(EventStore $eventStore, array $domainMessages): void
    {
        foreach ($domainMessages as $domainMessage) {
            $eventStore->append(
                $domainMessage->getId(),
                new DomainEventStream([$domainMessage])
            );
        }
    }

    private function createAggregateAwareDBALEventStore(AggregateType $aggregateType): AggregateAwareDBALEventStore
    {
        return new AggregateAwareDBALEventStore(
            $this->getConnection(),
            new SimpleInterfaceSerializer(),
            new SimpleInterfaceSerializer(),
            'event_store',
            $aggregateType
        );
    }

    /**
     * @param DomainMessage[] $domainMessages
     */
    private function checkEventStream(
        EventStream $eventStream,
        array $domainMessages,
        AggregateType $aggregateType
    ): void {
        $eventStream = $eventStream->withAggregateType($aggregateType->toString());

        $domainEventStreams = $eventStream();
        $domainEventStreams = iterator_to_array($domainEventStreams);

        $expectedDomainEventStreams = [];
        foreach ($domainMessages as $key => $domainMessage) {
            $metadataAsArray = $domainMessage->getMetadata()->serialize();
            if ($metadataAsArray['aggregate_type'] === $aggregateType->toString()) {
                $expectedDomainEventStreams[] = new DomainEventStream([$domainMessage]);
            }
        }

        $this->assertEquals(
            $expectedDomainEventStreams,
            $domainEventStreams
        );
    }
}
