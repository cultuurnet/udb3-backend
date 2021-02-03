<?php

namespace CultuurNet\Broadway\EventHandling;

use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventListenerInterface;
use CultuurNet\Broadway\Domain\DomainMessageIsReplayed;
use PHPUnit\Framework\TestCase;

class ReplayFilteringEventListenerTest extends TestCase
{
    /**
     * @var EventListenerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $eventListener;

    /**
     * @var FilteringEventListener
     */
    private $filteringEventListener;

    public function setUp()
    {
        $this->eventListener = $this->createMock(EventListenerInterface::class);

        $this->filteringEventListener = new ReplayFilteringEventListener($this->eventListener);
    }

    /**
     * @test
     */
    public function it_ignores_domain_messages_that_do_not_satisfy_the_specification()
    {
        $domainMessage = DomainMessage::recordNow(
            '44ba2574-aa50-4765-a0e5-38b046a13357',
            0,
            new Metadata([DomainMessageIsReplayed::METADATA_REPLAY_KEY => true]),
            new \stdClass()
        );

        $this->eventListener->expects($this->never())
            ->method('handle')
            ->with($domainMessage);

        $this->filteringEventListener->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_delegates_event_handling_to_its_decoratee_if_the_domain_message_satisfies_the_specification()
    {
        $domainMessage = DomainMessage::recordNow(
            '44ba2574-aa50-4765-a0e5-38b046a13357',
            0,
            new Metadata([DomainMessageIsReplayed::METADATA_REPLAY_KEY => false]),
            new \stdClass()
        );

        $this->eventListener->expects($this->once())
            ->method('handle')
            ->with($domainMessage);

        $this->filteringEventListener->handle($domainMessage);
    }
}
