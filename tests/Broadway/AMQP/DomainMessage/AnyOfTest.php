<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Broadway\AMQP\DomainMessage;

use Broadway\Domain\DateTime as BroadwayDateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Broadway\AMQP\Dummies\DummyEvent;
use CultuurNet\UDB3\Broadway\AMQP\Dummies\DummyEventNotSerializable;
use PHPUnit\Framework\TestCase;

class AnyOfTest extends TestCase
{
    /**
     * @var AnyOf
     */
    private $anyOf;

    protected function setUp()
    {
        $specifications = new SpecificationCollection();

        $payloadInNamespace = new PayloadInNamespace(
            '\CultuurNet\BroadwayAMQP\CrashTestDummies'
        );
        $specifications = $specifications->with(
            $payloadInNamespace
        );

        $payloadInstanceOf = new PayloadIsInstanceOf(DummyEvent::class);
        $specifications = $specifications->with(
            $payloadInstanceOf
        );

        $this->anyOf = new AnyOf($specifications);
    }

    /**
     * @test
     */
    public function it_is_satisfied_when_at_least_one_matches()
    {
        $domainMessage = new DomainMessage(
            'F68E71A1-DBB0-4542-AEE5-BD937E095F74',
            2,
            new Metadata(),
            new DummyEvent(
                'F68E71A1-DBB0-4542-AEE5-BD937E095F74',
                'test 123 456'
            ),
            BroadwayDateTime::fromString('2015-01-02T08:40:00+0100')
        );

        $this->assertTrue($this->anyOf->isSatisfiedBy($domainMessage));
    }

    /**
     * @test
     */
    public function it_is_not_satisfied_when_none_match()
    {
        $domainMessage = new DomainMessage(
            'F68E71A1-DBB0-4542-AEE5-BD937E095F74',
            2,
            new Metadata(),
            new DummyEventNotSerializable(
                'F68E71A1-DBB0-4542-AEE5-BD937E095F74',
                'test 123 456'
            ),
            BroadwayDateTime::fromString('2015-01-02T08:40:00+0100')
        );

        $this->assertFalse($this->anyOf->isSatisfiedBy($domainMessage));
    }
}
