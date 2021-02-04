<?php

namespace CultuurNet\BroadwayAMQP\Message\Body;

use Broadway\Domain\DateTime as BroadwayDateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\Serializer\SerializationException;
use CultuurNet\BroadwayAMQP\Dummies\DummyEvent;
use CultuurNet\BroadwayAMQP\Dummies\DummyEventNotSerializable;
use PHPUnit\Framework\TestCase;

class EntireDomainMessageBodyFactoryTest extends TestCase
{
    /**
     * @var EntireDomainMessageBodyFactory
     */
    private $entireDomainMessageBodyFactory;

    protected function setUp()
    {
        $this->entireDomainMessageBodyFactory = new EntireDomainMessageBodyFactory();
    }

    /**
     * @test
     */
    public function it_creates_body_from_entire_domain_message()
    {
        $domainMessage = new DomainMessage(
            'F68E71A1-DBB0-4542-AEE5-BD937E095F74',
            2,
            new Metadata(array(
                'meta' =>'data',
                'oranges' => 'apples'
            )),
            new DummyEvent(
                'F68E71A1-DBB0-4542-AEE5-BD937E095F74',
                'test 123 456'
            ),
            BroadwayDateTime::fromString('2015-01-02T08:40:00+0100')
        );

        $expectedBody = '{"id":"F68E71A1-DBB0-4542-AEE5-BD937E095F74","playhead":2,';
        $expectedBody .= '"metadata":{"meta":"data","oranges":"apples"},';
        $expectedBody .= '"payload":{"id":"F68E71A1-DBB0-4542-AEE5-BD937E095F74","content":"test 123 456"},';
        $expectedBody .= '"recorded_on":"2015-01-02T08:40:00.000000+01:00"}';

        $this->assertEquals(
            $expectedBody,
            $this->entireDomainMessageBodyFactory->createBody($domainMessage)
        );
    }

    /**
     * @test
     */
    public function it_throws_serialization_exception_when_payload_is_not_serializable()
    {
        $this->expectException(SerializationException::class);
        $this->expectExceptionMessage(
            'Unable to serialize CultuurNet\BroadwayAMQP\Dummies\DummyEventNotSerializable'
        );

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

        $this->entireDomainMessageBodyFactory->createBody($domainMessage);
    }
}
