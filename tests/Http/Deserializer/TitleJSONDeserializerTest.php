<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer;

use CultuurNet\UDB3\Deserializer\MissingValueException;
use CultuurNet\UDB3\Title;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class TitleJSONDeserializerTest extends TestCase
{
    /**
     * @var TitleJSONDeserializer
     */
    private $deserializer;

    public function setUp()
    {
        $this->deserializer = new TitleJSONDeserializer();
    }

    /**
     * @test
     */
    public function it_can_deserialize_a_valid_title()
    {
        $json = new StringLiteral('{"title": "Lorem ipsum"}');
        $expected = new Title('Lorem ipsum');
        $actual = $this->deserializer->deserialize($json);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_can_deserialize_with_optional_property_name()
    {
        $deserializer = new TitleJSONDeserializer(false, new StringLiteral('name'));

        $json = new StringLiteral('{"name": "Lorem ipsum"}');
        $expected = new Title('Lorem ipsum');

        $actual = $deserializer->deserialize($json);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_a_title_is_missing()
    {
        $json = new StringLiteral('{"foo": "bar"}');

        $this->expectException(MissingValueException::class);
        $this->expectExceptionMessage('Missing value for "title".');

        $this->deserializer->deserialize($json);
    }
}
