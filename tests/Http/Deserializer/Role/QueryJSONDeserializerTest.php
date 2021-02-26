<?php

namespace CultuurNet\UDB3\Http\Deserializer\Role;

use CultuurNet\UDB3\Deserializer\DataValidationException;
use CultuurNet\UDB3\Role\ValueObjects\Query;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class QueryJSONDeserializerTest extends TestCase
{
    /**
     * @var QueryJSONDeserializer
     */
    private $deserializer;

    protected function setUp()
    {
        $this->deserializer = new QueryJSONDeserializer();
    }

    /**
     * @test
     */
    public function it_checks_all_required_fields_are_present()
    {
        $data = new StringLiteral('{}');

        $expectedException = new DataValidationException();
        $expectedException->setValidationMessages(
            [
                'query' => 'Should not be empty.',
            ]
        );

        try {
            $this->deserializer->deserialize($data);
            $this->fail('No DataValidationException was thrown.');
        } catch (DataValidationException $e) {
            $this->assertEquals($expectedException->getValidationMessages(), $e->getValidationMessages());
        }
    }

    /**
     * @test
     */
    public function it_returns_a_query_object()
    {
        $data = new StringLiteral(
            json_encode(
                [
                    'query' => 'city:3000',
                ]
            )
        );

        $expectedQuery = new Query('city:3000');

        $actualQuery = $this->deserializer->deserialize($data);

        $this->assertEquals($expectedQuery, $actualQuery);
    }
}
