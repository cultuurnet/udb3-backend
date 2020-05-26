<?php

namespace CultuurNet\UDB3;

use CultuurNet\Deserializer\MissingValueException;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class DescriptionJSONDeserializerTest extends TestCase
{
    /**
     * @var DescriptionJSONDeserializer
     */
    private $deserializer;

    public function setUp()
    {
        $this->deserializer = new DescriptionJSONDeserializer();
    }

    /**
     * @test
     */
    public function it_can_deserialize_a_valid_description()
    {
        $json = new StringLiteral('{"description": "Lorem ipsum."}');
        $expected = new Description("Lorem ipsum.");
        $actual = $this->deserializer->deserialize($json);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_a_description_is_missing()
    {
        $json = new StringLiteral('{"foo": "bar"}');

        $this->expectException(
            MissingValueException::class,
            'Missing value for "description".'
        );

        $this->deserializer->deserialize($json);
    }
}
