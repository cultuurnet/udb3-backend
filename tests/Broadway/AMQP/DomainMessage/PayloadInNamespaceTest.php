<?php

namespace CultuurNet\BroadwayAMQP\DomainMessage;

use Broadway\Domain\DateTime as BroadwayDateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\BroadwayAMQP\Dummies\DummyEvent;
use PHPUnit\Framework\TestCase;

class PayloadInNamespaceTest extends TestCase
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
    public function it_satisfies_matching_payload_namespace()
    {
        $payloadInNamespace = new PayloadInNamespace(
            'CultuurNet\BroadwayAMQP\Dummies'
        );

        $this->assertTrue($payloadInNamespace->isSatisfiedBy(
            $this->domainMessage
        ));
    }

    /**
     * @test
     */
    public function it_does_not_satisfy_a_payload_namespace_that_does_not_match()
    {
        $payloadInNamespace = new PayloadInNamespace(
            'CultuurNet\BroadwayAMQP\CrashTestDummies'
        );

        $this->assertFalse($payloadInNamespace->isSatisfiedBy(
            $this->domainMessage
        ));
    }
}
