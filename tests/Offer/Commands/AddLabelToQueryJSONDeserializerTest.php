<?php

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\Deserializer\MissingValueException;
use CultuurNet\UDB3\Label;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class AddLabelToQueryJSONDeserializerTest extends TestCase
{
    /**
     * @var AddLabelToQueryJSONDeserializer
     */
    private $deserializer;

    public function setUp()
    {
        $this->deserializer = new AddLabelToQueryJSONDeserializer();
    }

    /**
     * @test
     */
    public function it_can_deserialize_a_valid_add_label_to_query_command()
    {
        $expectedLabel = new Label('foo');
        $expectedQuery = 'city:leuven';

        $json = new StringLiteral('{"label":"foo", "query":"city:leuven"}');

        $command = $this->deserializer->deserialize($json);

        $this->assertEquals($expectedLabel, $command->getLabel());
        $this->assertEquals($expectedQuery, $command->getQuery());
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_label_is_missing()
    {
        $json = new StringLiteral('{"query": "city:leuven"}');

        $this->expectException(
            MissingValueException::class,
            'Missing value "label".'
        );

        $this->deserializer->deserialize($json);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_label_is_empty()
    {
        $json = new StringLiteral('{"label": "", "query": "city:leuven"}');

        $this->expectException(
            MissingValueException::class,
            'Missing value "label".'
        );

        $this->deserializer->deserialize($json);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_query_is_missing()
    {
        $json = new StringLiteral('{"label": "foo"}');

        $this->expectException(
            MissingValueException::class,
            'Missing value "query".'
        );

        $this->deserializer->deserialize($json);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_query_is_empty()
    {
        $json = new StringLiteral('{"label": "foo", "query": ""}');

        $this->expectException(
            MissingValueException::class,
            'Missing value "query".'
        );

        $this->deserializer->deserialize($json);
    }
}
