<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

use CultuurNet\UDB3\Deserializer\MissingValueException;
use PHPUnit\Framework\TestCase;

class DescriptionJSONDeserializerTest extends TestCase
{
    /**
     * @var DescriptionJSONDeserializer
     */
    private $deserializer;

    public function setUp(): void
    {
        $this->deserializer = new DescriptionJSONDeserializer();
    }

    /**
     * @test
     */
    public function it_can_deserialize_a_valid_description()
    {
        $json = new StringLiteral('{"description": "Lorem ipsum."}');
        $expected = new Description('Lorem ipsum.');
        $actual = $this->deserializer->deserialize($json);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_a_description_is_missing()
    {
        $json = new StringLiteral('{"foo": "bar"}');

        $this->expectException(MissingValueException::class);
        $this->expectExceptionMessage('Missing value for "description".');

        $this->deserializer->deserialize($json);
    }
}
