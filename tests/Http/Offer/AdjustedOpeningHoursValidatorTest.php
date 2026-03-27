<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use PHPUnit\Framework\TestCase;

final class AdjustedOpeningHoursValidatorTest extends TestCase
{
    private AdjustedOpeningHoursValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new AdjustedOpeningHoursValidator();
    }

    /**
     * @test
     * @dataProvider validPeriodicAdjustedOpeningHoursProvider
     */
    public function it_accepts_valid_periodic_adjusted_opening_hours(object $data): void
    {
        $errors = $this->validator->validate($data);

        $this->assertEmpty($errors);
    }

    public function validPeriodicAdjustedOpeningHoursProvider(): array
    {
        return [
            'single adjusted opening hours entry' => [
                (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2026-01-01T00:00:00+00:00',
                    'endDate' => '2026-12-31T23:59:59+00:00',
                    'openingHoursAdjusted' => [
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
            'multiple non-overlapping entries' => [
                (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2026-01-01T00:00:00+00:00',
                    'endDate' => '2026-12-31T23:59:59+00:00',
                    'openingHoursAdjusted' => [
                        (object)[
                            'startDate' => '2026-12-21',
                            'endDate' => '2026-12-26',
                            'openingHours' => [
                                (object)[
                                    'opens' => '13:00',
                                    'closes' => '15:00',
                                    'dayOfWeek' => ['friday'],
                                ],
                            ],
                        ],
                        (object)[
                            'startDate' => '2026-12-27',
                            'endDate' => '2027-01-03',
                            'openingHours' => [
                                (object)[
                                    'opens' => '14:00',
                                    'closes' => '16:00',
                                    'dayOfWeek' => ['saturday'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'entry with description' => [
                (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2026-01-01T00:00:00+00:00',
                    'endDate' => '2026-12-31T23:59:59+00:00',
                    'openingHoursAdjusted' => [
                        (object)[
                            'startDate' => '2026-12-21',
                            'endDate' => '2027-01-03',
                            'description' => (object)['nl' => 'Kerstvakantie'],
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
            'entry at calendar boundaries' => [
                (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2026-01-01T00:00:00+00:00',
                    'endDate' => '2026-12-31T23:59:59+00:00',
                    'openingHoursAdjusted' => [
                        (object)[
                            'startDate' => '2026-01-01',
                            'endDate' => '2026-12-31',
                            'openingHours' => [
                                (object)[
                                    'opens' => '08:00',
                                    'closes' => '18:00',
                                    'dayOfWeek' => ['monday', 'tuesday'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider validPermanentAdjustedOpeningHoursProvider
     */
    public function it_accepts_valid_permanent_adjusted_opening_hours(object $data): void
    {
        $errors = $this->validator->validate($data);

        $this->assertEmpty($errors);
    }

    public function validPermanentAdjustedOpeningHoursProvider(): array
    {
        return [
            'any date for permanent' => [
                (object)[
                    'calendarType' => 'permanent',
                    'openingHoursAdjusted' => [
                        (object)[
                            'startDate' => '2027-12-21',
                            'endDate' => '2027-12-26',
                            'openingHours' => [
                                (object)[
                                    'opens' => '13:00',
                                    'closes' => '15:00',
                                    'dayOfWeek' => ['friday', 'saturday'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'multiple entries for permanent' => [
                (object)[
                    'calendarType' => 'permanent',
                    'openingHoursAdjusted' => [
                        (object)[
                            'startDate' => '2026-12-21',
                            'endDate' => '2026-12-26',
                            'openingHours' => [
                                (object)[
                                    'opens' => '13:00',
                                    'closes' => '15:00',
                                    'dayOfWeek' => ['friday'],
                                ],
                            ],
                        ],
                        (object)[
                            'startDate' => '2050-12-25',
                            'endDate' => '2050-12-26',
                            'openingHours' => [
                                (object)[
                                    'opens' => '10:00',
                                    'closes' => '12:00',
                                    'dayOfWeek' => ['saturday'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider invalidAdjustedOpeningHoursProvider
     */
    public function it_rejects_invalid_adjusted_opening_hours(object $data, string $expectedErrorPath, string $expectedErrorMessage): void
    {
        $errors = $this->validator->validate($data);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString($expectedErrorPath, $errors[0]->getPath());
        $this->assertStringContainsString($expectedErrorMessage, $errors[0]->getError());
    }

    public function invalidAdjustedOpeningHoursProvider(): array
    {
        return [
            'startDate after endDate' => [
                (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2026-01-01T00:00:00+00:00',
                    'endDate' => '2026-12-31T23:59:59+00:00',
                    'openingHoursAdjusted' => [
                        (object)[
                            'startDate' => '2026-12-26',
                            'endDate' => '2026-12-21',
                            'openingHours' => [
                                (object)[
                                    'opens' => '13:00',
                                    'closes' => '15:00',
                                    'dayOfWeek' => ['friday'],
                                ],
                            ],
                        ],
                    ],
                ],
                '/openingHoursAdjusted/0/endDate',
                'endDate should not be before startDate',
            ],
            'entry before periodic start' => [
                (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2026-03-01T00:00:00+00:00',
                    'endDate' => '2026-12-31T23:59:59+00:00',
                    'openingHoursAdjusted' => [
                        (object)[
                            'startDate' => '2026-01-01',
                            'endDate' => '2026-01-15',
                            'openingHours' => [
                                (object)[
                                    'opens' => '13:00',
                                    'closes' => '15:00',
                                    'dayOfWeek' => ['friday'],
                                ],
                            ],
                        ],
                    ],
                ],
                '/openingHoursAdjusted/0/startDate',
                'the start date of adjusted opening hours should not be before the calendar start date',
            ],
            'entry after periodic end' => [
                (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2026-01-01T00:00:00+00:00',
                    'endDate' => '2026-11-30T23:59:59+00:00',
                    'openingHoursAdjusted' => [
                        (object)[
                            'startDate' => '2026-12-21',
                            'endDate' => '2026-12-26',
                            'openingHours' => [
                                (object)[
                                    'opens' => '13:00',
                                    'closes' => '15:00',
                                    'dayOfWeek' => ['friday'],
                                ],
                            ],
                        ],
                    ],
                ],
                '/openingHoursAdjusted/0/endDate',
                'the end date of adjusted opening hours should not be after the calendar end date',
            ],
            'overlapping entries' => [
                (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2026-01-01T00:00:00+00:00',
                    'endDate' => '2026-12-31T23:59:59+00:00',
                    'openingHoursAdjusted' => [
                        (object)[
                            'startDate' => '2026-12-21',
                            'endDate' => '2026-12-26',
                            'openingHours' => [
                                (object)[
                                    'opens' => '13:00',
                                    'closes' => '15:00',
                                    'dayOfWeek' => ['friday'],
                                ],
                            ],
                        ],
                        (object)[
                            'startDate' => '2026-12-25',
                            'endDate' => '2026-12-31',
                            'openingHours' => [
                                (object)[
                                    'opens' => '14:00',
                                    'closes' => '16:00',
                                    'dayOfWeek' => ['saturday'],
                                ],
                            ],
                        ],
                    ],
                ],
                '/openingHoursAdjusted/1/startDate',
                'adjusted opening hours entries must not overlap',
            ],
        ];
    }

    /**
     * @test
     */
    public function it_ignores_when_adjusted_opening_hours_are_missing(): void
    {
        $data = (object)[
            'calendarType' => 'periodic',
            'startDate' => '2026-01-01T00:00:00+00:00',
            'endDate' => '2026-12-31T23:59:59+00:00',
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
            'endDate' => '2026-12-31T23:59:59+00:00',
            'openingHoursAdjusted' => [
                (object)[
                    'startDate' => '2026-12-21',
                    'endDate' => '2026-12-26',
                    'openingHours' => [
                        (object)[
                            'opens' => '13:00',
                            'closes' => '15:00',
                            'dayOfWeek' => ['friday'],
                        ],
                    ],
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
            'startDate' => '2026-01-01T00:00:00+00:00',
            // endDate missing
            'openingHoursAdjusted' => [
                (object)[
                    'startDate' => '2026-12-21',
                    'endDate' => '2027-01-03',
                    'openingHours' => [
                        (object)[
                            'opens' => '13:00',
                            'closes' => '15:00',
                            'dayOfWeek' => ['friday'],
                        ],
                    ],
                ],
            ],
        ];

        $errors = $this->validator->validate($data);

        // Should skip bounds check gracefully - no errors
        $this->assertEmpty($errors);
    }
}
