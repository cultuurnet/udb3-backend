<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use PHPUnit\Framework\TestCase;

final class UpdateCalendarRequestHandlerInvalidClosedDaysTest extends TestCase
{
    private ClosedDaysValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new ClosedDaysValidator();
    }

    /**
     * @test
     */
    public function it_rejects_closed_days_with_start_date_after_end_date(): void
    {
        $data = (object)[
            'calendarType' => 'periodic',
            'startDate' => '2024-01-01T00:00:00+00:00',
            'endDate' => '2024-12-31T23:59:59+00:00',
            'openingHoursClosedDays' => [
                (object)[
                    'startDate' => '2024-12-25T00:00:00+00:00',
                    'endDate' => '2024-12-24T23:59:59+00:00', // Before startDate!
                ],
            ],
        ];

        $errors = $this->validator->validate($data);

        $this->assertNotEmpty($errors);
        $this->assertInstanceOf(SchemaError::class, $errors[0]);
        $this->assertStringContainsString('endDate should not be before startDate', $errors[0]->getError());
    }

    /**
     * @test
     */
    public function it_rejects_closed_days_before_calendar_start_date_for_periodic(): void
    {
        $data = (object)[
            'calendarType' => 'periodic',
            'startDate' => '2024-03-01T00:00:00+00:00',
            'endDate' => '2024-12-31T23:59:59+00:00',
            'openingHoursClosedDays' => [
                (object)[
                    'startDate' => '2024-01-01T00:00:00+00:00', // Before calendar start!
                    'endDate' => '2024-01-01T23:59:59+00:00',
                ],
            ],
        ];

        $errors = $this->validator->validate($data);

        $this->assertNotEmpty($errors);
        $this->assertInstanceOf(SchemaError::class, $errors[0]);
        $this->assertStringContainsString('startDate should not be before the calendar startDate', $errors[0]->getError());
    }

    /**
     * @test
     */
    public function it_rejects_closed_days_after_calendar_end_date_for_periodic(): void
    {
        $data = (object)[
            'calendarType' => 'periodic',
            'startDate' => '2024-01-01T00:00:00+00:00',
            'endDate' => '2024-12-31T23:59:59+00:00',
            'openingHoursClosedDays' => [
                (object)[
                    'startDate' => '2025-01-01T00:00:00+00:00', // After calendar end!
                    'endDate' => '2025-01-01T23:59:59+00:00',
                ],
            ],
        ];

        $errors = $this->validator->validate($data);

        $this->assertNotEmpty($errors);
        $this->assertInstanceOf(SchemaError::class, $errors[0]);
        $this->assertStringContainsString('endDate should not be after the calendar endDate', $errors[0]->getError());
    }

    /**
     * @test
     */
    public function it_allows_valid_closed_days_within_periodic_range(): void
    {
        $data = (object)[
            'calendarType' => 'periodic',
            'startDate' => '2024-01-01T00:00:00+00:00',
            'endDate' => '2024-12-31T23:59:59+00:00',
            'openingHoursClosedDays' => [
                (object)[
                    'startDate' => '2024-12-25T00:00:00+00:00',
                    'endDate' => '2024-12-25T23:59:59+00:00',
                ],
            ],
        ];

        $errors = $this->validator->validate($data);

        $this->assertEmpty($errors);
    }

    /**
     * @test
     */
    public function it_allows_closed_days_for_permanent_calendar_without_bounds_check(): void
    {
        $data = (object)[
            'calendarType' => 'permanent',
            'openingHoursClosedDays' => [
                (object)[
                    'startDate' => '2025-12-25T00:00:00+00:00', // Any date is fine for permanent
                    'endDate' => '2025-12-25T23:59:59+00:00',
                ],
            ],
        ];

        $errors = $this->validator->validate($data);

        // Should be empty - permanent calendars have no date bounds
        $this->assertEmpty($errors);
    }

    /**
     * @test
     */
    public function it_allows_multiple_valid_closed_days(): void
    {
        $data = (object)[
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
        ];

        $errors = $this->validator->validate($data);

        $this->assertEmpty($errors);
    }

    /**
     * @test
     */
    public function it_ignores_validation_when_closed_days_are_missing(): void
    {
        $data = (object)[
            'calendarType' => 'periodic',
            'startDate' => '2024-01-01T00:00:00+00:00',
            'endDate' => '2024-12-31T23:59:59+00:00',
        ];

        $errors = $this->validator->validate($data);

        $this->assertEmpty($errors);
    }
}
