<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UDB2\DomainEvents;

use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\Url as LegacyUrl;

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
            new Url('http://foo.bar/event/foo')
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
            new Url('http://foo.bar/event/foo')
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

    public function testUrl()
    {
        $actorUpdated = $this->createActorUpdated();

        $this->assertEquals(
            new Url('http://foo.bar/event/foo'),
            $actorUpdated->getUrl()
        );
    }

    public function testSerialization()
    {
        $time = new \DateTimeImmutable('2016-04-15T16:06:11+0200');
        $eventCreated = $this->createActorUpdated($time);
        $expectedData = [
            'actorId' => '123',
            'time' => '2016-04-15T16:06:11+02:00',
            'author' => 'me@example.com',
            'url' => 'http://foo.bar/event/foo',
        ];

        $this->assertEquals(
            $expectedData,
            $eventCreated->serialize()
        );
    }
}
