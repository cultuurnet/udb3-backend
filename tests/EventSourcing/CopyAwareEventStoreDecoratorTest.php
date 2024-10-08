<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventSourcing;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventStore\EventStore;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CopyAwareEventStoreDecoratorTest extends TestCase
{
    /**
     * @var EventStore&MockObject
     */
    protected $eventStore;

    protected CopyAwareEventStoreDecorator $copyAwareEventStore;

    protected function setUp(): void
    {
        $this->eventStore = $this->createMock(EventStore::class);
        $this->copyAwareEventStore = new CopyAwareEventStoreDecorator($this->eventStore);
    }

    /**
     * This is a test case when the aggregate is not copied.
     * @test
     */
    public function it_should_return_the_aggregate_event_stream_when_it_contains_all_history(): void
    {
        $firstDomainMessage = $this->getDomainMessage(0, '');
        $secondDomainMessage = $this->getDomainMessage(1, '');
        $expectedEventStream = new DomainEventStream([$firstDomainMessage, $secondDomainMessage]);

        $this->eventStore->expects($this->once())
            ->method('load')
            ->with('94ae3a8f-596a-480b-b4f0-be7f8fe7e9b3')
            ->willReturn(new DomainEventStream([$firstDomainMessage, $secondDomainMessage]));

        $eventStream = $this->copyAwareEventStore->load('94ae3a8f-596a-480b-b4f0-be7f8fe7e9b3');

        $this->assertEquals($expectedEventStream, $eventStream);
    }

    /**
     * This is a test case when the aggregate is copied.
     * Both the parent and copy events are loaded.
     * @test
     */
    public function it_should_load_the_parent_history_when_aggregate_history_is_incomplete(): void
    {
        $parentFirstEventMessage = $this->getDomainMessage(0, '');
        $parentOtherEventMessage = $this->getDomainMessage(1, '');
        $aggregateOldestEventMessage = $this->getDomainMessage(2, '94ae3a8f-596a-480b-b4f0-be7f8fe7e9b3');
        $expectedEventStream = new DomainEventStream([
            $parentFirstEventMessage,
            $parentOtherEventMessage,
            $aggregateOldestEventMessage,
        ]);

        $this->eventStore->method('load')
            ->will($this->onConsecutiveCalls(
                new DomainEventStream([$aggregateOldestEventMessage]),
                new DomainEventStream([$parentFirstEventMessage, $parentOtherEventMessage])
            ));

        $eventStream = $this->copyAwareEventStore->load('422d7cb7-016c-42ca-a08e-277b3695ba41');

        $this->assertEquals($expectedEventStream, $eventStream);
    }

    /**
     * This is a test case when the aggregate is copied.
     * Both the parent and copy events are loaded.
     * But the events on the parent after the copy should be ignored.
     * @test
     */
    public function it_should_load_the_copied_history_when_aggregate_history_is_incomplete(): void
    {
        $parentFirstEventMessage = $this->getDomainMessage(0, '');
        $parentOtherEventMessage = $this->getDomainMessage(1, '');
        $parentAfterCopyEventMessage = $this->getDomainMessage(2, '');
        $aggregateOldestEventMessage = $this->getDomainMessage(2, '94ae3a8f-596a-480b-b4f0-be7f8fe7e9b3');
        $expectedEventStream = new DomainEventStream([
            $parentFirstEventMessage,
            $parentOtherEventMessage,
            $aggregateOldestEventMessage,
        ]);

        $this->eventStore->method('load')
            ->will($this->onConsecutiveCalls(
                new DomainEventStream([$aggregateOldestEventMessage]),
                new DomainEventStream([$parentFirstEventMessage, $parentOtherEventMessage, $parentAfterCopyEventMessage])
            ));

        $eventStream = $this->copyAwareEventStore->load('422d7cb7-016c-42ca-a08e-277b3695ba41');

        $this->assertEquals($expectedEventStream, $eventStream);
    }

    /**
     * This is a test case when the aggregate is copied.
     * Both the parent and copy events should be loaded.
     * It can handle gaps in playhead numbering.
     * @test
     */
    public function it_should_only_load_the_inherited_parent_history_when_there_are_jumps_in_playhead(): void
    {
        $parentFirstEventMessage = $this->getDomainMessage(0, '');
        $parentJumpedEventMessage = $this->getDomainMessage(2, '');
        $parentOldestEventMessage = $this->getDomainMessage(3, '');
        $aggregateOldestEventMessage = $this->getDomainMessage(4, '94ae3a8f-596a-480b-b4f0-be7f8fe7e9b3');
        $expectedEventStream = new DomainEventStream([
            $parentFirstEventMessage,
            $parentJumpedEventMessage,
            $parentOldestEventMessage,
            $aggregateOldestEventMessage,
        ]);

        $this->eventStore->method('load')
            ->will($this->onConsecutiveCalls(
                new DomainEventStream([$aggregateOldestEventMessage]),
                new DomainEventStream([$parentFirstEventMessage, $parentJumpedEventMessage, $parentOldestEventMessage])
            ));

        $eventStream = $this->copyAwareEventStore->load('422d7cb7-016c-42ca-a08e-277b3695ba41');

        $this->assertEquals($expectedEventStream, $eventStream);
    }

    /**
     * @test
     */
    public function it_should_load_the_complete_aggregate_history_when_there_are_multiple_ancestors(): void
    {
        $oldestAncestorEventMessage = $this->getDomainMessage(0, '');
        $parentCopiedEventMessage = $this->getDomainMessage(1, '94ae3a8f-596a-480b-b4f0-be7f8fe7e9b3');
        $aggregateCopiedEventMessage = $this->getDomainMessage(2, '41d4bfbc-eff5-4dc9-b24e-61179a6ada24');

        $expectedEventStream = new DomainEventStream([
            $oldestAncestorEventMessage,
            $parentCopiedEventMessage,
            $aggregateCopiedEventMessage,
        ]);

        $this->eventStore->method('load')
            ->will($this->onConsecutiveCalls(
                new DomainEventStream([$aggregateCopiedEventMessage]),
                new DomainEventStream([$parentCopiedEventMessage]),
                new DomainEventStream([$oldestAncestorEventMessage])
            ));

        $eventStream = $this->copyAwareEventStore->load('422d7cb7-016c-42ca-a08e-277b3695ba41');

        $this->assertEquals($expectedEventStream, $eventStream);
    }

    private function getDomainMessage(int $playhead, string $parentId): DomainMessage
    {
        $event = $this->createMock(AggregateCopiedEventInterface::class);
        $event->method('getParentAggregateId')->willReturn($parentId);
        return new DomainMessage('1-2-3', $playhead, new Metadata([]), $event, DateTime::now());
    }
}
