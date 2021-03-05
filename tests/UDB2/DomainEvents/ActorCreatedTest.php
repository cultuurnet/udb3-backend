<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UDB2\DomainEvents;

use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\Url;

class ActorCreatedTest extends TestCase
{
    public function testActorIdCanNotBeEmptyString()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('actor id can not be empty');

        new ActorCreated(
            new StringLiteral(''),
            new \DateTimeImmutable(),
            new StringLiteral(''),
            Url::fromNative('http://foo.bar/event/foo')
        );
    }

    private function createActorCreated(\DateTimeImmutable $time = null)
    {
        if (null === $time) {
            $time = new \DateTimeImmutable();
        }

        return new ActorCreated(
            new StringLiteral('123'),
            $time,
            new StringLiteral('me@example.com'),
            Url::fromNative('http://foo.bar/event/foo')
        );
    }

    public function testGetActorId()
    {
        $eventCreated = $this->createActorCreated();

        $this->assertEquals(
            new StringLiteral('123'),
            $eventCreated->getActorId()
        );
    }

    public function testGetAuthor()
    {
        $eventCreated = $this->createActorCreated();

        $this->assertEquals(
            new StringLiteral('me@example.com'),
            $eventCreated->getAuthor()
        );
    }

    public function testUrl()
    {
        $eventCreated = $this->createActorCreated();

        $this->assertEquals(
            Url::fromNative('http://foo.bar/event/foo'),
            $eventCreated->getUrl()
        );
    }

    public function testSerialization()
    {
        $time = new \DateTimeImmutable('2016-04-15T16:06:11+0200');
        $eventCreated = $this->createActorCreated($time);
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
