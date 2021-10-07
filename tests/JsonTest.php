<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

use JsonException;
use PHPUnit\Framework\TestCase;
use stdClass;

class JsonTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_encode_and_decode_an_array(): void
    {
        $given = ['foo'];
        $expected = '["foo"]';

        $encoded = Json::encode($given);
        $decoded = Json::decode($encoded);
        $assoc = Json::decodeAssociatively($encoded);

        $this->assertEquals($expected, $encoded);
        $this->assertEquals($given, $decoded);
        $this->assertEquals($given, $assoc);
    }

    /**
     * @test
     */
    public function it_can_encode_and_decode_an_object(): void
    {
        $given = (object) ['foo' => 'bar'];
        $expected = '{"foo":"bar"}';
        $expectedAssoc = ['foo' => 'bar'];

        $encoded = Json::encode($given);
        $decoded = Json::decode($encoded);
        $assoc = Json::decodeAssociatively($encoded);

        $this->assertEquals($expected, $encoded);
        $this->assertEquals($given, $decoded);
        $this->assertEquals($expectedAssoc, $assoc);
    }

    /**
     * @test
     */
    public function it_can_encode_and_decode_an_associative_array(): void
    {
        $given = ['foo' => 'bar'];
        $expected = '{"foo":"bar"}';

        $encoded = Json::encode($given);
        $decoded = Json::decode($encoded);
        $assoc = Json::decodeAssociatively($encoded);

        $this->assertEquals($expected, $encoded);
        $this->assertEquals((object) $given, $decoded);
        $this->assertEquals($given, $assoc);
    }

    /**
     * @test
     */
    public function it_can_encode_and_decode_a_string(): void
    {
        $given = 'foo';
        $expected = '"foo"';

        $encoded = Json::encode($given);
        $decoded = Json::decode($encoded);
        $assoc = Json::decodeAssociatively($encoded);

        $this->assertEquals($expected, $encoded);
        $this->assertEquals($given, $decoded);
        $this->assertEquals($given, $assoc);
    }

    /**
     * @test
     */
    public function it_can_encode_and_decode_an_integer(): void
    {
        $given = 11;
        $expected = '11';

        $encoded = Json::encode($given);
        $decoded = Json::decode($encoded);
        $assoc = Json::decodeAssociatively($encoded);

        $this->assertEquals($expected, $encoded);
        $this->assertEquals($given, $decoded);
        $this->assertEquals($given, $assoc);
    }

    /**
     * @test
     */
    public function it_can_encode_and_decode_a_boolean(): void
    {
        $given = true;
        $expected = 'true';

        $encoded = Json::encode($given);
        $decoded = Json::decode($encoded);
        $assoc = Json::decodeAssociatively($encoded);

        $this->assertEquals($expected, $encoded);
        $this->assertEquals($given, $decoded);
        $this->assertEquals($given, $assoc);
    }

    /**
     * @test
     */
    public function it_throws_when_it_can_not_encode(): void
    {
        $objectWithInfiniteNesting = new stdClass();
        $objectWithInfiniteNesting->object = $objectWithInfiniteNesting;
        $this->expectException(JsonException::class);
        Json::encode($objectWithInfiniteNesting);
    }

    /**
     * @test
     */
    public function it_throws_when_it_can_not_decode(): void
    {
        $invalidSyntax = '{';
        $this->expectException(JsonException::class);
        Json::decode($invalidSyntax);
    }

    /**
     * @test
     */
    public function it_throws_when_it_can_not_decode_associatively(): void
    {
        $invalidSyntax = '{';
        $this->expectException(JsonException::class);
        Json::decodeAssociatively($invalidSyntax);
    }
}
