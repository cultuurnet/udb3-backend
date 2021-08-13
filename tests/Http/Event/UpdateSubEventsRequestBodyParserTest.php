<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use PHPUnit\Framework\TestCase;

final class UpdateSubEventsRequestBodyParserTest extends TestCase
{
    use AssertApiProblemTrait;

    private UpdateSubEventsRequestBodyParser $parser;

    protected function setUp(): void
    {
        $this->parser = new UpdateSubEventsRequestBodyParser();
    }

    /**
     * @test
     * @dataProvider validDataProvider
     */
    public function it_does_not_throw_when_given_valid_data(array $data): void
    {
        $parsed = $this->parser->parse(
            (new Psr7RequestBuilder())->withBodyFromString(json_encode($data))->build('PUT')
        );
        $this->assertEquals($data, $parsed);
    }

    public function validDataProvider(): array
    {
        return [
            'one_subEvent_with_only_id' => [
                'data' => [
                    (object) [
                        'id' => 1,
                    ],
                ],
            ],
            'two_subEvents_with_only_id' => [
                'data' => [
                    (object) [
                        'id' => 1,
                    ],
                    (object) [
                        'id' => 2,
                    ],
                ],
            ],
            'one_subEvent_with_id_and_status_type_Unavailable' => [
                'data' => [
                    (object) [
                        'id' => 1,
                        'status' => (object) [
                            'type' => 'Unavailable',
                        ],
                    ],
                ],
            ],
            'two_subEvents_with_id_and_different_status_types' => [
                'data' => [
                    (object) [
                        'id' => 1,
                        'status' => (object) [
                            'type' => 'Unavailable',
                        ],
                    ],
                    (object) [
                        'id' => 2,
                        'status' => (object) [
                            'type' => 'Available',
                        ],
                    ],
                ],
            ],
            'one_subEvent_with_id_and_status_type_and_reason' => [
                'data' => [
                    (object) [
                        'id' => 1,
                        'status' => (object) [
                            'type' => 'Unavailable',
                            'reason' => (object) [
                                'nl' => 'Geannuleerd wegens covid',
                                'fr' => 'Franse tekst',
                            ]
                        ],
                    ],
                ],
            ],
            'one_subEvent_with_id_and_bookingAvailability_type' => [
                'data' => [
                    (object) [
                        'id' => 1,
                        'bookingAvailability' => (object) [
                            'type' => 'Unavailable',
                        ],
                    ],
                ],
            ],
            'one_subEvent_with_id_and_status_type_and_bookingAvailability_type' => [
                'data' => [
                    (object) [
                        'id' => 1,
                        'status' => (object) [
                            'type' => 'Available',
                        ],
                        'bookingAvailability' => (object) [
                            'type' => 'Unavailable',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider invalidDataProvider
     */
    public function it_throws_an_api_problem_when_given_with_valid_data(array $data, array $expectedSchemaErrors): void
    {
        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(...$expectedSchemaErrors),
            fn () => $this->parser->parse(
                (new Psr7RequestBuilder())->withBodyFromString(json_encode($data))->build('PUT')
            )
        );
    }

    public function invalidDataProvider(): array
    {
        return [
            'one_subEvent_without_id' => [
                'data' => [
                    (object) [],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/0', 'The required properties (id) are missing'),
                ],
            ],
            'two_subEvents_without_id' => [
                'data' => [
                    (object) [],
                    (object) [],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/', 'Array must have unique items'),
                ],
            ],
            'two_subEvents_one_with_id_and_the_other_without' => [
                'data' => [
                    (object) ['id' => 1],
                    (object) [],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/1', 'The required properties (id) are missing'),
                ],
            ],
            'two_subEvents_one_without_id_and_the_other_with_invalid_status_data_type' => [
                'data' => [
                    (object) ['id' => 1, 'status' => 'Unavailable'],
                    (object) [],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/0/status', 'The data (string) must match the type: object'),
                    new SchemaError('/1', 'The required properties (id) are missing'),
                ],
            ],
            'two_subEvents_one_with_invalid_status_type_and_the_other_valid' => [
                'data' => [
                    (object) ['id' => 1, 'status' => ['type' => 'invalid']],
                    (object) ['id' => 2],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/0/status/type', 'The data should match one item from enum'),
                ],
            ],
            'two_subEvents_one_with_valid_status_type_and_the_other_with_invalid_bookingAvailability_type' => [
                'data' => [
                    (object) ['id' => 1, 'status' => ['type' => 'Available']],
                    (object) ['id' => 2, 'bookingAvailability' => ['type' => 'invalid']],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/1/bookingAvailability/type', 'The data should match one item from enum'),
                ],
            ],
            'one_subEvent_with_invalid_status_reason' => [
                'data' => [
                    (object) [
                        'id' => 1,
                        'status' => [
                            'type' => 'Available',
                            'reason' => 'foo',
                        ],
                    ],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/0/status/reason', 'The data (string) must match the type: object'),
                ],
            ],
            'one_subEvent_with_only_status_reason_but_no_type' => [
                'data' => [
                    (object) [
                        'id' => 1,
                        'status' => (object) [
                            'reason' => (object) [
                                'nl' => 'Mijn reden in NL',
                            ],
                        ],
                    ],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/0/status', 'The required properties (type) are missing'),
                ],
            ],
        ];
    }
}
