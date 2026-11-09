<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use PHPUnit\Framework\TestCase;

final class UpdateCalendarValidatingRequestBodyParserTest extends TestCase
{
    use AssertApiProblemTrait;

    private UpdateCalendarValidatingRequestBodyParser $parser;

    protected function setUp(): void
    {
        $this->parser = new UpdateCalendarValidatingRequestBodyParser(JsonSchemaLocator::EVENT_CALENDAR_PUT);
    }

    /**
     * @test
     * @dataProvider validClosedDaysDataProvider
     */
    public function it_accepts_valid_closed_days(object $data): void
    {
        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromObject($data)
            ->build('PUT')
            ->withParsedBody($data);

        // Should not throw
        $this->expectNotToPerformAssertions();
        $this->parser->parse($request);
    }

    public function validClosedDaysDataProvider(): array
    {
        return [
            'periodic with single closed day' => [
                (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2024-01-01T00:00:00+00:00',
                    'endDate' => '2024-12-31T23:59:59+00:00',
                    'openingHours' => [],
                    'openingHoursClosedDays' => [
                        (object)[
                            'startDate' => '2024-12-25',
                            'endDate' => '2024-12-25',
                        ],
                    ],
                ],
            ],
            'permanent with multiple closed days' => [
                (object)[
                    'calendarType' => 'permanent',
                    'openingHours' => [],
                    'openingHoursClosedDays' => [
                        (object)[
                            'startDate' => '2024-01-01',
                            'endDate' => '2024-01-01',
                        ],
                        (object)[
                            'startDate' => '2025-12-25',
                            'endDate' => '2025-12-25',
                        ],
                    ],
                ],
            ],
            'periodic with closed day spanning multiple days' => [
                (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2024-01-01T00:00:00+00:00',
                    'endDate' => '2024-12-31T23:59:59+00:00',
                    'openingHours' => [],
                    'openingHoursClosedDays' => [
                        (object)[
                            'startDate' => '2024-12-24',
                            'endDate' => '2024-12-26',
                        ],
                    ],
                ],
            ],
            'permanent without closed days' => [
                (object)[
                    'calendarType' => 'permanent',
                    'openingHours' => [],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider invalidClosedDaysDataProvider
     */
    public function it_rejects_invalid_closed_days(object $data, string $errorPath, string $expectedErrorMessage): void
    {
        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromObject($data)
            ->build('PUT')
            ->withParsedBody($data);

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError($errorPath, $expectedErrorMessage)
            ),
            fn () => $this->parser->parse($request)
        );
    }

    public function invalidClosedDaysDataProvider(): array
    {
        return [
            'closed day startDate after endDate' => [
                (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2024-01-01T00:00:00+00:00',
                    'endDate' => '2024-12-31T23:59:59+00:00',
                    'openingHours' => [],
                    'openingHoursClosedDays' => [
                        (object)[
                            'startDate' => '2024-12-25',
                            'endDate' => '2024-12-24',
                        ],
                    ],
                ],
                '/openingHoursClosedDays/0/endDate',
                'endDate should not be before startDate',
            ],
            'closed day before periodic calendar startDate' => [
                (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2024-03-01T00:00:00+00:00',
                    'endDate' => '2024-12-31T23:59:59+00:00',
                    'openingHours' => [],
                    'openingHoursClosedDays' => [
                        (object)[
                            'startDate' => '2024-01-01',
                            'endDate' => '2024-01-01',
                        ],
                    ],
                ],
                '/openingHoursClosedDays/0/startDate',
                'the start date of a closed day should not be before the calendar start date',
            ],
            'closed day after periodic calendar endDate' => [
                (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2024-01-01T00:00:00+00:00',
                    'endDate' => '2024-12-31T23:59:59+00:00',
                    'openingHours' => [],
                    'openingHoursClosedDays' => [
                        (object)[
                            'startDate' => '2026-01-01',
                            'endDate' => '2026-01-01',
                        ],
                    ],
                ],
                '/openingHoursClosedDays/0/endDate',
                'the end date of a closed day should not be after the calendar end date',
            ],
            'multiple closed days with one invalid' => [
                (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2024-01-01T00:00:00+00:00',
                    'endDate' => '2024-12-31T23:59:59+00:00',
                    'openingHours' => [],
                    'openingHoursClosedDays' => [
                        (object)[
                            'startDate' => '2024-06-15',
                            'endDate' => '2024-06-15',
                        ],
                        (object)[
                            'startDate' => '2025-12-25',
                            'endDate' => '2025-12-25',
                        ],
                    ],
                ],
                '/openingHoursClosedDays/1/endDate',
                'the end date of a closed day should not be after the calendar end date',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider calendarTypesWithoutCapacityProvider
     */
    public function it_rejects_capacity_on_calendar_types_without_capacity(object $data): void
    {
        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromObject($data)
            ->build('PUT')
            ->withParsedBody($data);

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError('/subEvent/0/capacity', 'capacity is only allowed when calendarType is single or multiple')
            ),
            fn () => $this->parser->parse($request)
        );
    }

    public function calendarTypesWithoutCapacityProvider(): array
    {
        $subEvent = (object)[
            'startDate' => '2026-11-05T10:00:00+01:00',
            'endDate' => '2026-11-05T12:00:00+01:00',
            'capacity' => (object)['type' => 'Limited', 'maximum' => 30],
        ];

        return [
            'periodic' => [
                (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2026-11-01T00:00:00+01:00',
                    'endDate' => '2026-11-30T23:59:59+01:00',
                    'subEvent' => [$subEvent],
                ],
            ],
            'permanent' => [
                (object)[
                    'calendarType' => 'permanent',
                    'subEvent' => [$subEvent],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider calendarTypesWithCapacityProvider
     */
    public function it_accepts_capacity_on_calendar_types_with_capacity(object $data): void
    {
        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromObject($data)
            ->build('PUT')
            ->withParsedBody($data);

        $this->expectNotToPerformAssertions();
        $this->parser->parse($request);
    }

    public function calendarTypesWithCapacityProvider(): array
    {
        return [
            'single' => [
                (object)[
                    'calendarType' => 'single',
                    'subEvent' => [
                        (object)[
                            'startDate' => '2026-11-05T10:00:00+01:00',
                            'endDate' => '2026-11-05T12:00:00+01:00',
                            'capacity' => (object)['type' => 'Limited', 'maximum' => 30, 'taken' => 12],
                        ],
                    ],
                ],
            ],
            'multiple' => [
                (object)[
                    'calendarType' => 'multiple',
                    'subEvent' => [
                        (object)[
                            'startDate' => '2026-11-05T10:00:00+01:00',
                            'endDate' => '2026-11-05T12:00:00+01:00',
                            'capacity' => (object)['type' => 'Unlimited', 'taken' => 140],
                        ],
                        (object)[
                            'startDate' => '2026-11-06T10:00:00+01:00',
                            'endDate' => '2026-11-06T12:00:00+01:00',
                            'capacity' => (object)[],
                        ],
                        (object)[
                            'startDate' => '2026-11-07T10:00:00+01:00',
                            'endDate' => '2026-11-07T12:00:00+01:00',
                            'capacity' => (object)['type' => 'Limited', 'maximum' => 30],
                        ],
                        (object)[
                            'startDate' => '2026-11-08T10:00:00+01:00',
                            'endDate' => '2026-11-08T12:00:00+01:00',
                            'capacity' => (object)['type' => 'Unlimited'],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider invalidCapacityProvider
     */
    public function it_rejects_an_invalid_capacity(object $capacity, array $expectedErrors): void
    {
        $data = (object)[
            'calendarType' => 'single',
            'subEvent' => [
                (object)[
                    'startDate' => '2026-11-05T10:00:00+01:00',
                    'endDate' => '2026-11-05T12:00:00+01:00',
                    'capacity' => $capacity,
                ],
            ],
        ];

        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromObject($data)
            ->build('PUT')
            ->withParsedBody($data);

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(...$expectedErrors),
            fn () => $this->parser->parse($request)
        );
    }

    public function invalidCapacityProvider(): array
    {
        return [
            'limited without maximum' => [
                (object)['type' => 'Limited', 'taken' => 12],
                [new SchemaError('/subEvent/0/capacity', 'The required properties (maximum) are missing')],
            ],
            'unlimited with maximum' => [
                (object)['type' => 'Unlimited', 'maximum' => 30],
                [new SchemaError('/subEvent/0/capacity', 'Property \'maximum\' is not allowed')],
            ],
            'maximum below 1' => [
                (object)['type' => 'Limited', 'maximum' => 0],
                [new SchemaError('/subEvent/0/capacity/maximum', 'Number must be greater than or equal to 1')],
            ],
            'negative taken' => [
                (object)['type' => 'Unlimited', 'taken' => -1],
                [new SchemaError('/subEvent/0/capacity/taken', 'Number must be greater than or equal to 0')],
            ],
            'unknown type' => [
                (object)['type' => 'limited', 'maximum' => 30],
                [new SchemaError('/subEvent/0/capacity/type', 'The data should match one item from enum')],
            ],
            'without type' => [
                (object)['maximum' => 30],
                [
                    new SchemaError('/subEvent/0/capacity', 'The required properties (type) are missing'),
                    new SchemaError('/subEvent/0/capacity', 'Object must have at most 0 properties, 1 found'),
                ],
            ],
        ];
    }
}
