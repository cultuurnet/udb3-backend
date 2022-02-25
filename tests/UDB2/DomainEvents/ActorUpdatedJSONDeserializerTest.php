<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UDB2\DomainEvents;

use CultuurNet\UDB3\Deserializer\MissingValueException;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;
use CultuurNet\UDB3\StringLiteral;

class ActorUpdatedJSONDeserializerTest extends TestCase
{
    /**
     * @var ActorUpdatedJSONDeserializer
     */
    protected $deserializer;

    public function setUp()
    {
        $this->deserializer = new ActorUpdatedJSONDeserializer();
    }

    public function testRequiresActorId()
    {
        $this->expectException(MissingValueException::class);
        $this->expectExceptionMessage('actorId is missing');

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
                    "actorId": "foo",
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
                    "actorId": "foo",
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
                    "actorId": "foo",
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
                    "actorId": "foo",
                    "author": "me@example.com",
                    "time": "2015-02-20T20:39:09+0100"
                }'
            )
        );
    }

    public function testReturnsActorUpdated()
    {
        $actorUpdated = $this->deserializer->deserialize(
            new StringLiteral(
                '{
                    "actorId": "foo",
                    "time": "2015-02-20T20:39:09+0100",
                    "author": "me@example.com",
                    "url": "http://foo.bar/event/foo"
                }'
            )
        );

        $this->assertInstanceOf(
            ActorUpdated::class,
            $actorUpdated
        );

        $this->assertEquals(
            new StringLiteral('foo'),
            $actorUpdated->getactorId()
        );

        $this->assertEquals(
            new StringLiteral('me@example.com'),
            $actorUpdated->getAuthor()
        );

        $this->assertEquals(
            \DateTimeImmutable::createFromFormat(
                DateTimeInterface::ATOM,
                '2015-02-20T20:39:09+0100'
            ),
            $actorUpdated->getTime()
        );

        $this->assertEquals(
            new Url('http://foo.bar/event/foo'),
            $actorUpdated->getUrl()
        );
    }
}
