<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

use CultuurNet\UDB3\Deserializer\MissingValueException;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use PHPUnit\Framework\TestCase;

class LabelJSONDeserializerTest extends TestCase
{
    private Label $label;

    private LabelJSONDeserializer $deserializer;

    public function setUp()
    {
        $this->label = new Label(new LabelName('test-label'));
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

        $this->expectException(MissingValueException::class);
        $this->expectExceptionMessage('Missing value "label"!');

        $this->deserializer->deserialize($json);
    }
}
