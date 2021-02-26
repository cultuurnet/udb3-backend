<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UDB2\DomainEvents;

use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\Url;

class ActorUpdatedTest extends TestCase
{
    public function testActorIdCanNotBeEmptyString()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('actor id can not be empty');

        new ActorUpdated(
            new StringLiteral(''),
            new \DateTimeImmutable(),
            new StringLiteral(''),
            Url::fromNative('http://foo.bar/event/foo')
        );
    }

    private function createActorUpdated(\DateTimeImmutable $time = null)
    {
        if (null === $time) {
            $time = new \DateTimeImmutable();
        }

        return new ActorUpdated(
            new StringLiteral('123'),
            $time,
            new StringLiteral('me@example.com'),
            Url::fromNative('http://foo.bar/event/foo')
        );
    }

    public function testGetActorId()
    {
        $actorUpdated = $this->createActorUpdated();

        $this->assertEquals(
            new StringLiteral('123'),
            $actorUpdated->getActorId()
        );
    }

    public function testGetAuthor()
    {
        $actorUpdated = $this->createActorUpdated();

        $this->assertEquals(
            new StringLiteral('me@example.com'),
            $actorUpdated->getAuthor()
        );
    }

    public function testTime()
    {
        $time = new \DateTimeImmutable();
        $expectedTime = clone $time;

        $actorUpdated = $this->createActorUpdated($time);

        // Adjustments to the time after creating the event should
        // not affect the event time.
        $time->modify('+5 days');

        $this->assertEquals(
            $expectedTime,
            $actorUpdated->getTime()
        );
    }

    public function testUrl()
    {
        $actorUpdated = $this->createActorUpdated();

        $this->assertEquals(
            Url::fromNative('http://foo.bar/event/foo'),
            $actorUpdated->getUrl()
        );
    }

    public function testSerialization()
    {
        $time = new \DateTimeImmutable('2016-04-15T16:06:11+0200');
        $eventCreated = $this->createActorUpdated($time);
        $expectedData = [
            'actorId' => '123',
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
