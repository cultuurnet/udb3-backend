<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Deserializer\MissingValueException;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use PHPUnit\Framework\TestCase;

class AddLabelToQueryJSONDeserializerTest extends TestCase
{
    private AddLabelToQueryJSONDeserializer $deserializer;

    public function setUp(): void
    {
        $this->deserializer = new AddLabelToQueryJSONDeserializer();
    }

    /**
     * @test
     */
    public function it_can_deserialize_a_valid_add_label_to_query_command(): void
    {
        $expectedLabel = new Label(new LabelName('foo'));
        $expectedQuery = 'city:leuven';

        $json = '{"label":"foo", "query":"city:leuven"}';

        $command = $this->deserializer->deserialize($json);

        $this->assertEquals($expectedLabel, $command->getLabel());
        $this->assertEquals($expectedQuery, $command->getQuery());
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_label_is_missing(): void
    {
        $json = '{"query": "city:leuven"}';

        $this->expectException(MissingValueException::class);
        $this->expectExceptionMessage('Missing value "label".');

        $this->deserializer->deserialize($json);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_label_is_empty(): void
    {
        $json = '{"label": "", "query": "city:leuven"}';

        $this->expectException(MissingValueException::class);
        $this->expectExceptionMessage('Missing value "label".');

        $this->deserializer->deserialize($json);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_query_is_missing(): void
    {
        $json = '{"label": "foo"}';

        $this->expectException(MissingValueException::class);
        $this->expectExceptionMessage('Missing value "query".');

        $this->deserializer->deserialize($json);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_query_is_empty(): void
    {
        $json = '{"label": "foo", "query": ""}';

        $this->expectException(MissingValueException::class);
        $this->expectExceptionMessage('Missing value "query".');

        $this->deserializer->deserialize($json);
    }
}
