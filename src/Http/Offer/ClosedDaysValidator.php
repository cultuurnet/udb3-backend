<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\DateTimeInvalid;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use DateTimeImmutable;

final class ClosedDaysValidator
{
    /**
     * @return SchemaError[]
     */
    public function validate(object $data): array
    {
        if (!isset($data->openingHoursClosedDays) || !is_array($data->openingHoursClosedDays)) {
            // Error(s) will be reported by the Schema validation.
            return [];
        }

        $errors = [];
        foreach ($data->openingHoursClosedDays as $index => $closedDayData) {
            if (!isset($closedDayData->startDate, $closedDayData->endDate) ||
                !is_string($closedDayData->startDate) ||
                !is_string($closedDayData->endDate)) {
                // Error(s) will be reported by the Schema validation.
                continue;
            }

            try {
                $startDate = $this->parseDateTime($closedDayData->startDate);
                $endDate = $this->parseDateTime($closedDayData->endDate);
            } catch (DateTimeInvalid $e) {
                // Date format error(s) will be reported by the Schema validation.
                continue;
            }

            if ($startDate > $endDate) {
                $errors[] = new SchemaError(
                    '/openingHoursClosedDays/' . $index . '/endDate',
                    'endDate should not be before startDate'
                );
            }

            // For periodic calendars, validate that closed days are within the periodic range
            if (isset($data->calendarType) && $data->calendarType === 'periodic') {
                if (!isset($data->startDate, $data->endDate) ||
                    !is_string($data->startDate) ||
                    !is_string($data->endDate)) {
                    // Errors will be reported by Schema validation or DateRangeValidator
                    continue;
                }

                try {
                    $periodicStart = $this->parseDateTime($data->startDate);
                    $periodicEnd = $this->parseDateTime($data->endDate);
                } catch (DateTimeInvalid $e) {
                    // Errors will be reported by DateRangeValidator
                    continue;
                }

                if ($startDate < $periodicStart) {
                    $errors[] = new SchemaError(
                        '/openingHoursClosedDays/' . $index . '/startDate',
                        'startDate should not be before the calendar startDate'
                    );
                }

                if ($endDate > $periodicEnd) {
                    $errors[] = new SchemaError(
                        '/openingHoursClosedDays/' . $index . '/endDate',
                        'endDate should not be after the calendar endDate'
                    );
                }
            }
        }

        return $errors;
    }

    private function parseDateTime(string $dateString): DateTimeImmutable
    {
        // Try parsing as date-only format first (YYYY-MM-DD)
        $dateOnly = DateTimeImmutable::createFromFormat('Y-m-d', $dateString);
        if ($dateOnly instanceof DateTimeImmutable) {
            return $dateOnly;
        }

        // Fall back to ISO8601 datetime format
        return DateTimeFactory::fromISO8601($dateString);
    }
}
