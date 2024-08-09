<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Broadway\EventHandling;

use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Broadway\Domain\DomainMessageIsReplayed;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ReplayFilteringEventListenerTest extends TestCase
{
    /**
     * @var EventListener&MockObject
     */
    private $eventListener;

    /**
     * @var FilteringEventListener
     */
    private $filteringEventListener;

    public function setUp(): void
    {
        $this->eventListener = $this->createMock(EventListener::class);

        $this->filteringEventListener = new ReplayFilteringEventListener($this->eventListener);
    }

    /**
     * @test
     */
    public function it_ignores_domain_messages_that_do_not_satisfy_the_specification(): void
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
    public function it_delegates_event_handling_to_its_decoratee_if_the_domain_message_satisfies_the_specification(): void
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
