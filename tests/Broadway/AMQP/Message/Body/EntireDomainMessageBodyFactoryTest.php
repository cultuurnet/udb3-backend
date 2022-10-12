<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Broadway\AMQP\Message\Body;

use Broadway\Domain\DateTime as BroadwayDateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\Serializer\SerializationException;
use CultuurNet\UDB3\Broadway\AMQP\Dummies\DummyEvent;
use CultuurNet\UDB3\Broadway\AMQP\Dummies\DummyEventNotSerializable;
use PHPUnit\Framework\TestCase;

class EntireDomainMessageBodyFactoryTest extends TestCase
{
    /**
     * @var EntireDomainMessageBodyFactory
     */
    private $entireDomainMessageBodyFactory;

    protected function setUp(): void
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
            new Metadata([
                'meta' =>'data',
                'oranges' => 'apples',
            ]),
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
            'Unable to serialize ' . DummyEventNotSerializable::class
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
