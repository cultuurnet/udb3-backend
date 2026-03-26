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
        $this->parser->parse($request);

        $this->assertTrue(true);
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
}
