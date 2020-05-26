<?php

namespace CultuurNet\UDB3;

use CultuurNet\Deserializer\MissingValueException;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class LabelJSONDeserializerTest extends TestCase
{
    /**
     * @var Label
     */
    private $label;

    /**
     * @var LabelJSONDeserializer
     */
    private $deserializer;

    public function setUp()
    {
        $this->label = new Label('test-label');
        $this->deserializer = new LabelJSONDeserializer();
    }

    /**
     * @test
     */
    public function it_can_deserialize_a_valid_label()
    {
        $json = new StringLiteral('{"label": "test-label"}');
        $label = $this->deserializer->deserialize($json);
        $this->assertEquals($this->label, $label);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_no_label_is_found()
    {
        $json = new StringLiteral('{"foo": "bar"}');

        $this->expectException(
            MissingValueException::class,
            'Missing value "label"!'
        );

        $this->deserializer->deserialize($json);
    }
}
