<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\ApiProblem;

use PHPUnit\Framework\TestCase;

class ApiProblemTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_the_minimal_required_properties(): void
    {
        $problem = ApiProblem::internalServerError();

        $this->assertEquals('about:blank', $problem->getType());
        $this->assertEquals('Internal Server Error', $problem->getTitle());
        $this->assertEquals(500, $problem->getStatus());
    }

    /**
     * @test
     */
    public function it_can_have_a_detail(): void
    {
        $problem = ApiProblem::urlNotFound('"foo" is not a valid email address');
        $this->assertEquals('"foo" is not a valid email address', $problem->getDetail());
    }

    /**
     * @test
     */
    public function it_can_have_schema_errors_if_error_is_invalid_body_data(): void
    {
        $problem = ApiProblem::bodyInvalidData(new SchemaError('/mock', 'Property mock should not be empty'));
        $this->assertEquals(
            [new SchemaError('/mock', 'Property mock should not be empty')],
            $problem->getSchemaErrors()
        );
    }

    /**
     * @test
     */
    public function it_can_be_converted_to_a_json_array(): void
    {
        $problem = ApiProblem::bodyInvalidData(new SchemaError('/mock', 'Property mock should not be empty'));
        $this->assertEquals(
            [
                'type' => 'https://api.publiq.be/probs/body/invalid-data',
                'title' => 'Invalid body data',
                'status' => 400,
                'schemaErrors' => [
                    [
                        'jsonPointer' => '/mock',
                        'error' => 'Property mock should not be empty',
                    ],
                ],
            ],
            $problem->toArray()
        );
    }

    /**
     * @test
     */
    public function it_can_construct_a_required_field_missing(): void
    {
        $problem = ApiProblem::requiredFieldMissing('field_name');
        $this->assertEquals(
            [
                'type' => 'https://api.publiq.be/probs/body/invalid-data',
                'title' => 'Invalid body data',
                'status' => 400,
                'schemaErrors' => [
                    [
                        'jsonPointer' => '/',
                        'error' => 'The property \'field_name\' is required.',
                    ],
                ],
            ],
            $problem->toArray()
        );
    }

    /**
     * @test
     */
    public function it_can_construct_unsupported_media_type_problem(): void
    {
        $problem = ApiProblem::unsupportedMediaType();
        $this->assertEquals(
            [
                'type' => 'https://api.publiq.be/probs/header/unsupported-media-type',
                'title' => 'Unsupported media type',
                'status' => 415,
            ],
            $problem->toArray()
        );
    }
}
