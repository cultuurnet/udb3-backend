<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use PHPUnit\Framework\TestCase;

final class ClosedDaysValidatorTest extends TestCase
{
    private ClosedDaysValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new ClosedDaysValidator();
    }

    /**
     * @test
     * @dataProvider validPeriodicClosedDaysProvider
     */
    public function it_accepts_valid_periodic_closed_days(object $data): void
    {
        $errors = $this->validator->validate($data);

        $this->assertEmpty($errors);
    }

    public function validPeriodicClosedDaysProvider(): array
    {
        return [
            'single closed day' => [
                (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2024-01-01T00:00:00+00:00',
                    'endDate' => '2024-12-31T23:59:59+00:00',
                    'openingHoursClosedDays' => [
                        (object)[
                            'startDate' => '2024-12-25T00:00:00+00:00',
                            'endDate' => '2024-12-25T23:59:59+00:00',
                        ],
                    ],
                ],
            ],
            'multiple closed days' => [
                (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2024-01-01T00:00:00+00:00',
                    'endDate' => '2024-12-31T23:59:59+00:00',
                    'openingHoursClosedDays' => [
                        (object)[
                            'startDate' => '2024-01-01T00:00:00+00:00',
                            'endDate' => '2024-01-01T23:59:59+00:00',
                        ],
                        (object)[
                            'startDate' => '2024-12-25T00:00:00+00:00',
                            'endDate' => '2024-12-26T23:59:59+00:00',
                        ],
                    ],
                ],
            ],
            'closed day spanning same date' => [
                (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2024-01-01T00:00:00+00:00',
                    'endDate' => '2024-12-31T23:59:59+00:00',
                    'openingHoursClosedDays' => [
                        (object)[
                            'startDate' => '2024-07-21T00:00:00+00:00',
                            'endDate' => '2024-07-21T00:00:00+00:00',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider validPermanentClosedDaysProvider
     */
    public function it_accepts_valid_permanent_closed_days(object $data): void
    {
        $errors = $this->validator->validate($data);

        $this->assertEmpty($errors);
    }

    public function validPermanentClosedDaysProvider(): array
    {
        return [
            'any date for permanent' => [
                (object)[
                    'calendarType' => 'permanent',
                    'openingHoursClosedDays' => [
                        (object)[
                            'startDate' => '2025-12-25T00:00:00+00:00',
                            'endDate' => '2025-12-25T23:59:59+00:00',
                        ],
                    ],
                ],
            ],
            'multiple dates for permanent' => [
                (object)[
                    'calendarType' => 'permanent',
                    'openingHoursClosedDays' => [
                        (object)[
                            'startDate' => '2024-01-01T00:00:00+00:00',
                            'endDate' => '2024-01-01T23:59:59+00:00',
                        ],
                        (object)[
                            'startDate' => '2050-12-25T00:00:00+00:00',
                            'endDate' => '2050-12-25T23:59:59+00:00',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider invalidClosedDaysProvider
     */
    public function it_rejects_invalid_closed_days(object $data, string $expectedError): void
    {
        $errors = $this->validator->validate($data);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString($expectedError, $errors[0]->getError());
    }

    public function invalidClosedDaysProvider(): array
    {
        return [
            'startDate after endDate' => [
                (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2024-01-01T00:00:00+00:00',
                    'endDate' => '2024-12-31T23:59:59+00:00',
                    'openingHoursClosedDays' => [
                        (object)[
                            'startDate' => '2024-12-25T00:00:00+00:00',
                            'endDate' => '2024-12-24T23:59:59+00:00',
                        ],
                    ],
                ],
                'endDate should not be before startDate',
            ],
            'closed day before periodic start' => [
                (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2024-03-01T00:00:00+00:00',
                    'endDate' => '2024-12-31T23:59:59+00:00',
                    'openingHoursClosedDays' => [
                        (object)[
                            'startDate' => '2024-01-01T00:00:00+00:00',
                            'endDate' => '2024-01-01T23:59:59+00:00',
                        ],
                    ],
                ],
                'startDate should not be before the calendar startDate',
            ],
            'closed day after periodic end' => [
                (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2024-01-01T00:00:00+00:00',
                    'endDate' => '2024-12-31T23:59:59+00:00',
                    'openingHoursClosedDays' => [
                        (object)[
                            'startDate' => '2025-01-01T00:00:00+00:00',
                            'endDate' => '2025-01-01T23:59:59+00:00',
                        ],
                    ],
                ],
                'endDate should not be after the calendar endDate',
            ],
        ];
    }

    /**
     * @test
     */
    public function it_ignores_when_closed_days_are_missing(): void
    {
        $data = (object)[
            'calendarType' => 'periodic',
            'startDate' => '2024-01-01T00:00:00+00:00',
            'endDate' => '2024-12-31T23:59:59+00:00',
        ];

        $errors = $this->validator->validate($data);

        $this->assertEmpty($errors);
    }

    /**
     * @test
     */
    public function it_skips_bounds_check_when_calendar_startDate_is_missing(): void
    {
        $data = (object)[
            'calendarType' => 'periodic',
            // startDate missing
            'endDate' => '2024-12-31T23:59:59+00:00',
            'openingHoursClosedDays' => [
                (object)[
                    'startDate' => '2024-01-01T00:00:00+00:00',
                    'endDate' => '2024-01-01T23:59:59+00:00',
                ],
            ],
        ];

        $errors = $this->validator->validate($data);

        // Should skip bounds check gracefully - no errors
        $this->assertEmpty($errors);
    }

    /**
     * @test
     */
    public function it_skips_bounds_check_when_calendar_endDate_is_missing(): void
    {
        $data = (object)[
            'calendarType' => 'periodic',
            'startDate' => '2024-01-01T00:00:00+00:00',
            // endDate missing
            'openingHoursClosedDays' => [
                (object)[
                    'startDate' => '2024-12-25T00:00:00+00:00',
                    'endDate' => '2025-01-01T23:59:59+00:00',
                ],
            ],
        ];

        $errors = $this->validator->validate($data);

        // Should skip bounds check gracefully - no errors
        $this->assertEmpty($errors);
    }

    /**
     * @test
     */
    public function it_skips_entry_when_closed_day_startDate_is_not_string(): void
    {
        $data = (object)[
            'calendarType' => 'periodic',
            'startDate' => '2024-01-01T00:00:00+00:00',
            'endDate' => '2024-12-31T23:59:59+00:00',
            'openingHoursClosedDays' => [
                (object)[
                    'startDate' => 123, // Not a string!
                    'endDate' => '2024-01-01T23:59:59+00:00',
                ],
            ],
        ];

        $errors = $this->validator->validate($data);

        // Should skip entry gracefully - schema validation will catch it
        $this->assertEmpty($errors);
    }

    /**
     * @test
     */
    public function it_skips_entry_when_closed_day_missing_fields(): void
    {
        $data = (object)[
            'calendarType' => 'periodic',
            'startDate' => '2024-01-01T00:00:00+00:00',
            'endDate' => '2024-12-31T23:59:59+00:00',
            'openingHoursClosedDays' => [
                (object)[
                    // Missing startDate
                    'endDate' => '2024-01-01T23:59:59+00:00',
                ],
            ],
        ];

        $errors = $this->validator->validate($data);

        // Should skip entry gracefully - schema validation will catch it
        $this->assertEmpty($errors);
    }

    /**
     * @test
     */
    public function it_skips_entry_with_malformed_date_format(): void
    {
        $data = (object)[
            'calendarType' => 'periodic',
            'startDate' => '2024-01-01T00:00:00+00:00',
            'endDate' => '2024-12-31T23:59:59+00:00',
            'openingHoursClosedDays' => [
                (object)[
                    'startDate' => 'not-a-date',
                    'endDate' => '2024-01-01T23:59:59+00:00',
                ],
            ],
        ];

        $errors = $this->validator->validate($data);

        // Should skip entry gracefully - schema validation will catch it
        $this->assertEmpty($errors);
    }
}
