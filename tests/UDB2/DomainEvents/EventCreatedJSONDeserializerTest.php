<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UDB2\DomainEvents;

use CultuurNet\UDB3\Deserializer\MissingValueException;
use DateTime;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\Url;

class EventCreatedJSONDeserializerTest extends TestCase
{
    /**
     * @var EventCreatedJSONDeserializer
     */
    protected $deserializer;

    public function setUp()
    {
        $this->deserializer = new EventCreatedJSONDeserializer();
    }

    public function testRequiresEventId()
    {
        $this->expectException(MissingValueException::class);
        $this->expectExceptionMessage('eventId is missing');

        $this->deserializer->deserialize(
            new StringLiteral('{}')
        );
    }

    public function testRequiresTime()
    {
        $this->expectException(MissingValueException::class);
        $this->expectExceptionMessage('time is missing');

        $this->deserializer->deserialize(
            new StringLiteral(
                '{
                    "eventId": "foo",
                    "url": "http://foo.bar/event/foo"
                }'
            )
        );
    }

    public function testTimeNeedsToBeISO8601Formatted()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('invalid time provided');

        $this->deserializer->deserialize(
            new StringLiteral(
                '{
                    "eventId": "foo",
                    "author": "me@example.com",
                    "time": "2014-12-12",
                    "url": "http://foo.bar/event/foo"
                }'
            )
        );
    }

    public function testRequiresAuthor()
    {
        $this->expectException(MissingValueException::class);
        $this->expectExceptionMessage('author is missing');

        $this->deserializer->deserialize(
            new StringLiteral(
                '{
                    "eventId": "foo",
                    "time": "2015-02-20T20:39:09+0100",
                    "url": "http://foo.bar/event/foo"
                }'
            )
        );
    }

    public function testRequiresUrl()
    {
        $this->expectException(MissingValueException::class);
        $this->expectExceptionMessage('url is missing');

        $this->deserializer->deserialize(
            new StringLiteral(
                '{
                    "eventId": "foo",
                    "author": "me@example.com",
                    "time": "2015-02-20T20:39:09+0100"
                }'
            )
        );
    }

    public function testReturnsEventCreated()
    {
        /** @var EventCreated $eventCreated */
        $eventCreated = $this->deserializer->deserialize(
            new StringLiteral(
                '{
                    "eventId": "foo",
                    "time": "2015-02-20T20:39:09+0100",
                    "author": "me@example.com",
                    "url": "http://foo.bar/event/foo"
                }'
            )
        );

        $this->assertInstanceOf(
            EventCreated::class,
            $eventCreated
        );

        $this->assertEquals(
            new StringLiteral('foo'),
            $eventCreated->getEventId()
        );

        $this->assertEquals(
            new StringLiteral('me@example.com'),
            $eventCreated->getAuthor()
        );

        $this->assertEquals(
            Url::fromNative('http://foo.bar/event/foo'),
            $eventCreated->getUrl()
        );

        $this->assertEquals(
            \DateTimeImmutable::createFromFormat(
                DateTime::ISO8601,
                '2015-02-20T20:39:09+0100'
            ),
            $eventCreated->getTime()
        );
    }
}
