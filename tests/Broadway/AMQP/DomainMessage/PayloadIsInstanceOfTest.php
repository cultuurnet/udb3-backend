<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Broadway\AMQP\DomainMessage;

use Broadway\Domain\DateTime as BroadwayDateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Broadway\AMQP\Dummies\DummyEvent;
use CultuurNet\UDB3\Broadway\AMQP\Dummies\DummyEventNotSerializable;
use CultuurNet\UDB3\Broadway\AMQP\Dummies\DummyEventSubclass;
use PHPUnit\Framework\TestCase;

class PayloadIsInstanceOfTest extends TestCase
{
    private DomainMessage $domainMessage;

    protected function setUp(): void
    {
        $this->domainMessage = new DomainMessage(
            'F68E71A1-DBB0-4542-AEE5-BD937E095F74',
            2,
            new Metadata(),
            new DummyEvent(
                'F68E71A1-DBB0-4542-AEE5-BD937E095F74',
                'test 123 456'
            ),
            BroadwayDateTime::fromString('2015-01-02T08:40:00+0100')
        );
    }

    /**
     * @test
     */
    public function it_satisfies_payload_is_an_instanceof(): void
    {
        $payloadIsInstanceOf = new PayloadIsInstanceOf(
            DummyEvent::class
        );

        $this->assertTrue($payloadIsInstanceOf->isSatisfiedBy(
            $this->domainMessage
        ));
    }

    /**
     * @test
     */
    public function it_satisfies_payload_is_a_subclass_of(): void
    {
        $domainMessage = new DomainMessage(
            'F68E71A1-DBB0-4542-AEE5-BD937E095F74',
            2,
            new Metadata(),
            new DummyEventSubclass(
                'F68E71A1-DBB0-4542-AEE5-BD937E095F74',
                'test 123 456'
            ),
            BroadwayDateTime::fromString('2015-01-02T08:40:00+0100')
        );

        $payloadIsInstanceOf = new PayloadIsInstanceOf(
            DummyEvent::class
        );

        $this->assertTrue($payloadIsInstanceOf->isSatisfiedBy(
            $domainMessage
        ));
    }

    /**
     * @test
     */
    public function it_does_not_satisfy_different_payload_instance_type(): void
    {
        $payloadIsInstanceOf = new PayloadIsInstanceOf(
            DummyEventNotSerializable::class
        );

        $this->assertFalse($payloadIsInstanceOf->isSatisfiedBy(
            $this->domainMessage
        ));
    }
}
