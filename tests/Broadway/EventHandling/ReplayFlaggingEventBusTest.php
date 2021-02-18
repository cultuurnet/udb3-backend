<?php

namespace CultuurNet\UDB3\Broadway\EventHandling;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventListener;
use Broadway\EventHandling\SimpleEventBus;
use PHPUnit\Framework\TestCase;

class ReplayFlaggingEventBusTest extends TestCase
{
    /**
     * @var SimpleEventBus
     */
    private $eventBus;

    /**
     * @var ReplayFlaggingEventBus
     */
    private $replayAwareEventBus;

    /**
     * @var EventListener|\PHPUnit_Framework_MockObject_MockObject
     */
    private $processManager;

    /**
     * @var ReplayFilteringEventListener
     */
    private $replayFilteringEventListener;

    /**
     * @var array
     */
    private $handledEvents;

    public function setUp()
    {
        $this->handledEvents = [];

        $this->eventBus = new SimpleEventBus();
        $this->replayAwareEventBus = new ReplayFlaggingEventBus($this->eventBus);

        $this->processManager = $this->createMock(EventListener::class);
        $this->processManager->expects($this->any())
            ->method('handle')
            ->willReturnCallback(
                function (DomainMessage $domainMessage) {
                    $this->handledEvents[] = $domainMessage;
                }
            );

        $this->replayFilteringEventListener = new ReplayFilteringEventListener($this->processManager);
        $this->replayAwareEventBus->subscribe($this->replayFilteringEventListener);
    }

    /**
     * @test
     */
    public function it_flags_all_published_messages_as_replayed_during_replay_mode()
    {
        $stream = $this->getMockEventStream();

        $this->replayAwareEventBus->startReplayMode();
        $this->replayAwareEventBus->publish($stream);
        $this->replayAwareEventBus->stopReplayMode();

        $this->assertMessageCountHandledByProcessManager(0);
    }

    /**
     * @test
     */
    public function it_stops_flagging_published_messages_as_replayed_after_replay_mode()
    {
        $stream = $this->getMockEventStream();

        $this->replayAwareEventBus->startReplayMode();
        $this->replayAwareEventBus->publish($stream);
        $this->replayAwareEventBus->stopReplayMode();

        $this->replayAwareEventBus->publish($stream);

        $this->assertMessageCountHandledByProcessManager(2);
    }

    /**
     * @test
     */
    public function it_does_not_start_replay_mode_on_construction()
    {
        $stream = $this->getMockEventStream();
        $this->replayAwareEventBus->publish($stream);
        $this->assertMessageCountHandledByProcessManager(2);
    }

    /**
     * @param int $count
     */
    private function assertMessageCountHandledByProcessManager($count)
    {
        $this->assertCount($count, $this->handledEvents);
    }

    /**
     * @return DomainEventStream
     */
    private function getMockEventStream()
    {
        return new DomainEventStream($this->getMockMessages());
    }

    /**
     * @return DomainMessage[]
     */
    private function getMockMessages()
    {
        return [
            new DomainMessage(
                'a95bcb45-6952-42e8-bf22-911d8785387b',
                0,
                new Metadata(['author' => 'john.doe']),
                new \stdClass(),
                DateTime::fromString('2017-02-13 05:56:22.082300')
            ),
            new DomainMessage(
                'a95bcb45-6952-42e8-bf22-911d8785387b',
                1,
                new Metadata(['author' => 'jane.doe']),
                new \stdClass(),
                DateTime::fromString('2017-03-02 07:23:36.092320')
            ),
        ];
    }
}
