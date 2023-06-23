<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Broadway\AMQP\Message\Properties;

use CultuurNet\UDB3\Broadway\AMQP\Dummies\DummyEvent;
use PHPUnit\Framework\TestCase;

class ContentTypeLookupTest extends TestCase
{
    protected ContentTypeLookupInterface $contentTypeLookup;

    public function setUp(): void
    {
        $this->contentTypeLookup = new ContentTypeLookup();
    }

    /**
     * @test
     */
    public function it_can_return_the_content_type_when_added_to_the_mapping(): void
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
    public function it_throws_runtime_exception_when_setting_the_same_content_type(): void
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
    public function it_throws_a_runtime_exception_when_the_content_type_cannot_be_found(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Unable to find the content type of CultuurNet\BroadwayAMQP\Dummies\DummyEvent'
        );

        $payloadClass = 'CultuurNet\BroadwayAMQP\Dummies\DummyEvent';

        $this->contentTypeLookup->getContentType($payloadClass);
    }
}
