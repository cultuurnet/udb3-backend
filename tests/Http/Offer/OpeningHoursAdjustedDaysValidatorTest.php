<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use PHPUnit\Framework\TestCase;

final class OpeningHoursAdjustedDaysValidatorTest extends TestCase
{
    private OpeningHoursAdjustedDaysValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new OpeningHoursAdjustedDaysValidator();
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
            'multiple non-overlapping entries' => [
                (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2026-01-01T00:00:00+00:00',
                    'endDate' => '2026-12-31T23:59:59+00:00',
                    'openingHoursAdjustedDays' => [
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
            ],
            'entry with description' => [
                (object)[
                    'calendarType' => 'periodic',
                    'startDate' => '2026-01-01T00:00:00+00:00',
                    'endDate' => '2026-12-31T23:59:59+00:00',
                    'openingHoursAdjustedDays' => [
                        (object)[
                            'startDate' => '2026-12-21',
                            'endDate' => '2026-12-31',
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
                    'openingHoursAdjustedDays' => [
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
                    'openingHoursAdjustedDays' => [
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
                    'openingHoursAdjustedDays' => [
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
     */
    public function it_rejects_entry_where_start_date_is_after_end_date(): void
    {
        $data = (object)[
            'calendarType' => 'periodic',
            'startDate' => '2026-01-01T00:00:00+00:00',
            'endDate' => '2026-12-31T23:59:59+00:00',
            'openingHoursAdjustedDays' => [
                (object)[
                    'startDate' => '2026-12-26',
                    'endDate' => '2026-12-21',
                    'openingHours' => [
                        (object)['opens' => '13:00', 'closes' => '15:00', 'dayOfWeek' => ['friday']],
                    ],
                ],
            ],
        ];

        $errors = $this->validator->validate($data);

        $this->assertNotEmpty($errors);
        $this->assertSame('/openingHoursAdjustedDays/0/endDate', $errors[0]->getJsonPointer());
        $this->assertSame('startDate should not be later than endDate', $errors[0]->getError());
    }

    /**
     * @test
     */
    public function it_rejects_entry_that_starts_before_periodic_calendar_start(): void
    {
        $data = (object)[
            'calendarType' => 'periodic',
            'startDate' => '2026-03-01T00:00:00+00:00',
            'endDate' => '2026-12-31T23:59:59+00:00',
            'openingHoursAdjustedDays' => [
                (object)[
                    'startDate' => '2026-01-01',
                    'endDate' => '2026-01-15',
                    'openingHours' => [
                        (object)['opens' => '13:00', 'closes' => '15:00', 'dayOfWeek' => ['friday']],
                    ],
                ],
            ],
        ];

        $errors = $this->validator->validate($data);

        $this->assertNotEmpty($errors);
        $this->assertSame('/openingHoursAdjustedDays/0/startDate', $errors[0]->getJsonPointer());
        $this->assertSame('the start date of adjusted opening hours should not be before the calendar start date', $errors[0]->getError());
    }

    /**
     * @test
     */
    public function it_rejects_entry_that_ends_after_periodic_calendar_end(): void
    {
        $data = (object)[
            'calendarType' => 'periodic',
            'startDate' => '2026-01-01T00:00:00+00:00',
            'endDate' => '2026-11-30T23:59:59+00:00',
            'openingHoursAdjustedDays' => [
                (object)[
                    'startDate' => '2026-12-21',
                    'endDate' => '2026-12-26',
                    'openingHours' => [
                        (object)['opens' => '13:00', 'closes' => '15:00', 'dayOfWeek' => ['friday']],
                    ],
                ],
            ],
        ];

        $errors = $this->validator->validate($data);

        $this->assertNotEmpty($errors);
        $this->assertSame('/openingHoursAdjustedDays/0/endDate', $errors[0]->getJsonPointer());
        $this->assertSame('the end date of adjusted opening hours should not be after the calendar end date', $errors[0]->getError());
    }

    /**
     * @test
     */
    public function it_rejects_entry_with_invalid_opens_time(): void
    {
        $data = (object)[
            'calendarType' => 'periodic',
            'startDate' => '2026-01-01T00:00:00+00:00',
            'endDate' => '2026-12-31T23:59:59+00:00',
            'openingHoursAdjustedDays' => [
                (object)[
                    'startDate' => '2026-12-21',
                    'endDate' => '2026-12-26',
                    'openingHours' => [
                        (object)['opens' => '25:00', 'closes' => '15:00', 'dayOfWeek' => ['friday']],
                    ],
                ],
            ],
        ];

        $errors = $this->validator->validate($data);

        $this->assertNotEmpty($errors);
        $this->assertSame('/openingHoursAdjustedDays/0/openingHours/0/opens', $errors[0]->getJsonPointer());
        $this->assertSame('Invalid time format (hh:mm)', $errors[0]->getError());
    }

    /**
     * @test
     */
    public function it_rejects_entry_with_invalid_closes_time(): void
    {
        $data = (object)[
            'calendarType' => 'periodic',
            'startDate' => '2026-01-01T00:00:00+00:00',
            'endDate' => '2026-12-31T23:59:59+00:00',
            'openingHoursAdjustedDays' => [
                (object)[
                    'startDate' => '2026-12-21',
                    'endDate' => '2026-12-26',
                    'openingHours' => [
                        (object)['opens' => '13:00', 'closes' => '24:60', 'dayOfWeek' => ['friday']],
                    ],
                ],
            ],
        ];

        $errors = $this->validator->validate($data);

        $this->assertNotEmpty($errors);
        $this->assertSame('/openingHoursAdjustedDays/0/openingHours/0/closes', $errors[0]->getJsonPointer());
        $this->assertSame('Invalid time format (hh:mm)', $errors[0]->getError());
    }

    /**
     * @test
     */
    public function it_rejects_overlapping_entries(): void
    {
        $data = (object)[
            'calendarType' => 'periodic',
            'startDate' => '2026-01-01T00:00:00+00:00',
            'endDate' => '2026-12-31T23:59:59+00:00',
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
        ];

        $errors = $this->validator->validate($data);

        $this->assertNotEmpty($errors);
        $this->assertSame('/openingHoursAdjustedDays/1/startDate', $errors[0]->getJsonPointer());
        $this->assertSame('adjusted opening hours entries must not overlap', $errors[0]->getError());
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
            'openingHoursAdjustedDays' => [
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
            'openingHoursAdjustedDays' => [
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
