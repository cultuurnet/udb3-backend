<?php

namespace CultuurNet\UDB3\Broadway\AMQP\Message\Properties;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Broadway\AMQP\Dummies\DummyEvent;
use CultuurNet\UDB3\Broadway\AMQP\Dummies\DummyEventNotSerializable;
use PHPUnit\Framework\TestCase;

class ContentTypePropertiesFactoryTest extends TestCase
{
    /**
     * @var ContentTypeLookup
     */
    private $contentTypeLookup;

    /**
     * @var ContentTypePropertiesFactory
     */
    private $contentTypePropertiesFactory;

    public function setUp()
    {
        $this->contentTypeLookup = (new ContentTypeLookup())
            ->withContentType(
                DummyEvent::class,
                'application/vnd.cultuurnet.udb3-events.dummy-event+json'
            )
            ->withContentType(
                DummyEventNotSerializable::class,
                'application/vnd.cultuurnet.udb3-events.dummy-event-not-serializable+json'
            );

        $this->contentTypePropertiesFactory = new ContentTypePropertiesFactory($this->contentTypeLookup);
    }

    /**
     * @test
     * @dataProvider contentTypeDataProvider
     *
     * @param mixed $payload
     * @param string $expectedContentType
     */
    public function it_determines_content_type_by_payload_class($payload, $expectedContentType)
    {
        $domainMessage = new DomainMessage(
            '097c36dc-6019-44e2-b6e0-c57d32d8f97c',
            0,
            new Metadata(),
            $payload,
            DateTime::now()
        );

        $expectedProperties = ['content_type' => $expectedContentType];

        $actualProperties = $this->contentTypePropertiesFactory->createProperties($domainMessage);

        $this->assertEquals($expectedProperties, $actualProperties);
    }

    /**
     * @return array
     */
    public function contentTypeDataProvider()
    {
        return [
            [
                new DummyEvent(1, 'foo'),
                'application/vnd.cultuurnet.udb3-events.dummy-event+json',
            ],
            [
                new DummyEventNotSerializable(2, 'bar'),
                'application/vnd.cultuurnet.udb3-events.dummy-event-not-serializable+json',
            ],
        ];
    }
}
