<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Broadway\AMQP\Message\Properties;

use CultuurNet\UDB3\Broadway\AMQP\Dummies\DummyEvent;
use PHPUnit\Framework\TestCase;

class ContentTypeLookupTest extends TestCase
{
    /**
     * @var ContentTypeLookup
     */
    protected $contentTypeLookup;

    public function setUp()
    {
        $this->contentTypeLookup = new ContentTypeLookup();
    }

    /**
     * @test
     */
    public function it_can_return_the_content_type_when_added_to_the_mapping()
    {
        $this->contentTypeLookup = $this->contentTypeLookup->withContentType(
            DummyEvent::class,
            'application/vnd.cultuurnet.udb3-events.dummy-event+json'
        );

        $expectedContentType = 'application/vnd.cultuurnet.udb3-events.dummy-event+json';
        $contentType = $this->contentTypeLookup->getContentType(DummyEvent::class);

        $this->assertEquals($expectedContentType, $contentType);
    }

    /**
     * @test
     */
    public function it_throws_invalid_argument_exception_when_payload_class_is_not_a_string()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Value for argument payloadClass should be a string'
        );

        $this->contentTypeLookup->withContentType(
            1,
            'application/vnd.cultuurnet.udb3-events.dummy-event+json'
        );
    }

    /**
     * @test
     */
    public function it_throws_invalid_argument_exception_when_content_type_is_not_a_string()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Value for argument contentType should be a string'
        );

        $this->contentTypeLookup->withContentType(
            DummyEvent::class,
            1
        );
    }

    /**
     * @test
     */
    public function it_throws_runtime_exception_when_setting_the_same_content_type()
    {
        $contentTypeLookup = $this->contentTypeLookup->withContentType(
            DummyEvent::class,
            'application/vnd.cultuurnet.udb3-events.dummy-event+json'
        );

        $this->expectException(\InvalidArgumentException::class);

        $contentTypeLookup->withContentType(
            DummyEvent::class,
            'application/vnd.cultuurnet.udb3-events.dummy-event+json'
        );
    }

    /**
     * @test
     */
    public function it_throws_a_runtime_exception_when_the_content_type_cannot_be_found()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Unable to find the content type of CultuurNet\BroadwayAMQP\Dummies\DummyEvent'
        );

        $payloadClass = 'CultuurNet\BroadwayAMQP\Dummies\DummyEvent';

        $this->contentTypeLookup->getContentType($payloadClass);
    }
}
