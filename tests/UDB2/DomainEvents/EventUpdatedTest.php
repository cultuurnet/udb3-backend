<?php

namespace CultuurNet\UDB3\UDB2\DomainEvents;

use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\Url;

class EventUpdatedTest extends TestCase
{
    public function testEventIdCanNotBeEmptyString()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('event id can not be empty');

        new EventUpdated(
            new StringLiteral(''),
            new \DateTimeImmutable(),
            new StringLiteral(''),
            Url::fromNative('http://foo.bar/event/foo')
        );
    }

    private function createEventUpdated(\DateTimeImmutable $time = null)
    {
        if (null === $time) {
            $time = new \DateTimeImmutable();
        }

        return new EventUpdated(
            new StringLiteral('123'),
            $time,
            new StringLiteral('me@example.com'),
            Url::fromNative('http://foo.bar/event/foo')
        );
    }

    public function testGetEventId()
    {
        $eventUpdated = $this->createEventUpdated();

        $this->assertEquals(
            new StringLiteral('123'),
            $eventUpdated->getEventId()
        );
    }

    public function testGetAuthor()
    {
        $eventUpdated = $this->createEventUpdated();

        $this->assertEquals(
            new StringLiteral('me@example.com'),
            $eventUpdated->getAuthor()
        );
    }

    public function testTime()
    {
        $time = new \DateTimeImmutable();
        $expectedTime = clone $time;

        $eventUpdated = $this->createEventUpdated($time);

        // Adjustments to the time after creating the event should
        // not affect the event time.
        $time->modify('+5 days');

        $this->assertEquals(
            $expectedTime,
            $eventUpdated->getTime()
        );
    }

    public function testGetUrl()
    {
        $eventUpdated = $this->createEventUpdated();

        $this->assertEquals(
            Url::fromNative('http://foo.bar/event/foo'),
            $eventUpdated->getUrl()
        );
    }

    public function testSerialization()
    {
        $time = new \DateTimeImmutable("2016-04-15T16:06:11+0200");
        $eventCreated = $this->createEventUpdated($time);
        $expectedData = [
            "eventId" => "123",
            "time" => "2016-04-15T16:06:11+0200",
            "author" => "me@example.com",
            "url" => "http://foo.bar/event/foo",
        ];

        $this->assertEquals(
            $expectedData,
            $eventCreated->serialize()
        );
    }
}
