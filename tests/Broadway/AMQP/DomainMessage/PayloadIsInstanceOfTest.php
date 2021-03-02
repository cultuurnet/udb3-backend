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
    /**
     * @var DomainMessage
     */
    private $domainMessage;

    protected function setUp()
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
    public function it_satisfies_payload_is_an_instanceof()
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
    public function it_satisfies_payload_is_a_subclass_of()
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
    public function it_does_not_satisfy_different_payload_instance_type()
    {
        $payloadIsInstanceOf = new PayloadIsInstanceOf(
            DummyEventNotSerializable::class
        );

        $this->assertFalse($payloadIsInstanceOf->isSatisfiedBy(
            $this->domainMessage
        ));
    }

    /**
     * @test
     */
    public function it_throws_invalid_argument_exception_when_created_with_wrong_type_for_typename()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Value for argument typeName should be a string');

        new PayloadIsInstanceOf(1);
    }
}
