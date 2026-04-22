<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use PHPUnit\Framework\TestCase;

final class CalendarValidatingRequestBodyParserAdjustedDaysTest extends TestCase
{
    use AssertApiProblemTrait;

    private CalendarValidatingRequestBodyParser $parser;

    protected function setUp(): void
    {
        $this->parser = new CalendarValidatingRequestBodyParser();
    }

    /**
     * @test
     * @dataProvider validAdjustedDaysDataProvider
     */
    public function it_accepts_valid_adjusted_days(object $data): void
    {
        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromObject($data)
            ->build('PUT')
            ->withParsedBody($data);

        // Should not throw
        $this->parser->parse($request);

        $this->assertTrue(true);
    }

    public function validAdjustedDaysDataProvider(): array
    {
        return [
            'periodic with single adjusted period' => [
                (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2026-01-01T00:00:00+00:00',
                    'endDate' => '2026-12-31T23:59:59+00:00',
                    'openingHours' => [],
                    'openingHoursAdjustedDays' => [
                        (object)[
                            'startDate' => '2026-12-21',
                            'endDate' => '2026-12-26',
                            'openingHours' => [
                                (object)[
                                    'opens' => '13:00',
                                    'closes' => '15:00',
                                    'dayOfWeek' => ['friday', 'saturday', 'sunday'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'periodic with multiple non-overlapping adjusted periods' => [
                (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2026-01-01T00:00:00+00:00',
                    'endDate' => '2026-12-31T23:59:59+00:00',
                    'openingHours' => [],
                    'openingHoursAdjustedDays' => [
                        (object)[
                            'startDate' => '2026-12-21',
                            'endDate' => '2026-12-26',
                            'openingHours' => [
                                (object)['opens' => '13:00', 'closes' => '15:00', 'dayOfWeek' => ['friday']],
                            ],
                        ],
                        (object)[
                            'startDate' => '2026-12-27',
                            'endDate' => '2026-12-31',
                            'openingHours' => [
                                (object)['opens' => '14:00', 'closes' => '16:00', 'dayOfWeek' => ['sunday']],
                            ],
                        ],
                    ],
                ],
            ],
            'permanent with adjusted period' => [
                (object)[
                    'calendarType' => 'permanent',
                    'openingHours' => [],
                    'openingHoursAdjustedDays' => [
                        (object)[
                            'startDate' => '2026-12-24',
                            'endDate' => '2026-12-26',
                            'openingHours' => [
                                (object)['opens' => '10:00', 'closes' => '14:00', 'dayOfWeek' => ['friday']],
                            ],
                        ],
                    ],
                ],
            ],
            'periodic without adjusted days' => [
                (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2026-01-01T00:00:00+00:00',
                    'endDate' => '2026-12-31T23:59:59+00:00',
                    'openingHours' => [],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider invalidAdjustedDaysDataProvider
     */
    public function it_rejects_invalid_adjusted_days(object $data, string $errorPath, string $expectedErrorMessage): void
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

    public function invalidAdjustedDaysDataProvider(): array
    {
        return [
            'adjusted period startDate after endDate' => [
                (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2026-01-01T00:00:00+00:00',
                    'endDate' => '2026-12-31T23:59:59+00:00',
                    'openingHours' => [],
                    'openingHoursAdjustedDays' => [
                        (object)[
                            'startDate' => '2026-12-26',
                            'endDate' => '2026-12-21',
                            'openingHours' => [
                                (object)['opens' => '13:00', 'closes' => '15:00', 'dayOfWeek' => ['friday']],
                            ],
                        ],
                    ],
                ],
                '/openingHoursAdjustedDays/0/endDate',
                'startDate should not be later than endDate',
            ],
            'adjusted period starts before periodic calendar startDate' => [
                (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2026-03-01T00:00:00+00:00',
                    'endDate' => '2026-12-31T23:59:59+00:00',
                    'openingHours' => [],
                    'openingHoursAdjustedDays' => [
                        (object)[
                            'startDate' => '2026-01-01',
                            'endDate' => '2026-01-15',
                            'openingHours' => [
                                (object)['opens' => '13:00', 'closes' => '15:00', 'dayOfWeek' => ['friday']],
                            ],
                        ],
                    ],
                ],
                '/openingHoursAdjustedDays/0/startDate',
                'the start date of adjusted opening hours should not be before the calendar start date',
            ],
            'adjusted period ends after periodic calendar endDate' => [
                (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2026-01-01T00:00:00+00:00',
                    'endDate' => '2026-11-30T23:59:59+00:00',
                    'openingHours' => [],
                    'openingHoursAdjustedDays' => [
                        (object)[
                            'startDate' => '2026-12-21',
                            'endDate' => '2026-12-26',
                            'openingHours' => [
                                (object)['opens' => '13:00', 'closes' => '15:00', 'dayOfWeek' => ['friday']],
                            ],
                        ],
                    ],
                ],
                '/openingHoursAdjustedDays/0/endDate',
                'the end date of adjusted opening hours should not be after the calendar end date',
            ],
            'overlapping adjusted periods' => [
                (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2026-01-01T00:00:00+00:00',
                    'endDate' => '2026-12-31T23:59:59+00:00',
                    'openingHours' => [],
                    'openingHoursAdjustedDays' => [
                        (object)[
                            'startDate' => '2026-12-21',
                            'endDate' => '2026-12-26',
                            'openingHours' => [
                                (object)['opens' => '13:00', 'closes' => '15:00', 'dayOfWeek' => ['friday']],
                            ],
                        ],
                        (object)[
                            'startDate' => '2026-12-25',
                            'endDate' => '2026-12-31',
                            'openingHours' => [
                                (object)['opens' => '14:00', 'closes' => '16:00', 'dayOfWeek' => ['saturday']],
                            ],
                        ],
                    ],
                ],
                '/openingHoursAdjustedDays/1/startDate',
                'adjusted opening hours entries must not overlap',
            ],
        ];
    }
}
