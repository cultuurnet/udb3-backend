<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UDB2\DomainEvents;

use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\Url;

class EventCreatedTest extends TestCase
{
    public function testEventIdCanNotBeEmptyString()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('event id can not be empty');

        new EventCreated(
            new StringLiteral(''),
            new \DateTimeImmutable(),
            new StringLiteral(''),
            Url::fromNative('http://foo.bar/event/foo')
        );
    }

    private function createEventCreated(\DateTimeImmutable $time = null)
    {
        if (null === $time) {
            $time = new \DateTimeImmutable();
        }

        return new EventCreated(
            new StringLiteral('123'),
            $time,
            new StringLiteral('me@example.com'),
            Url::fromNative('http://foo.bar/event/foo')
        );
    }

    public function testGetEventId()
    {
        $eventCreated = $this->createEventCreated();

        $this->assertEquals(
            new StringLiteral('123'),
            $eventCreated->getEventId()
        );
    }

    public function testGetAuthor()
    {
        $eventCreated = $this->createEventCreated();

        $this->assertEquals(
            new StringLiteral('me@example.com'),
            $eventCreated->getAuthor()
        );
    }

    public function testGetUrl()
    {
        $eventCreated = $this->createEventCreated();

        $this->assertEquals(
            Url::fromNative('http://foo.bar/event/foo'),
            $eventCreated->getUrl()
        );
    }

    public function testSerialization()
    {
        $time = new \DateTimeImmutable('2016-04-15T16:06:11+0200');
        $eventCreated = $this->createEventCreated($time);
        $expectedData = [
            'eventId' => '123',
            'time' => '2016-04-15T16:06:11+0200',
            'author' => 'me@example.com',
            'url' => 'http://foo.bar/event/foo',
        ];

        $this->assertEquals(
            $expectedData,
            $eventCreated->serialize()
        );
    }
}
