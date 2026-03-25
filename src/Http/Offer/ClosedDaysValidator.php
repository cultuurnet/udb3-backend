<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Validates closed days for periodic and permanent calendars.
 *
 * Note: The JSON schema already validates date format (Y-m-d or ISO8601) via "format" and pattern rules.
 * If parsing exceptions occur during validation, this indicates a schema validation bypass.
 * Such exceptions should be logged to Sentry as they represent a system integrity issue.
 */
final class ClosedDaysValidator
{
    /**
     * @return SchemaError[]
     */
    public function validate(object $data): array
    {
        if (!isset($data->openingHoursClosedDays) || !is_array($data->openingHoursClosedDays)) {
            return [];
        }

        $errors = [];
        foreach ($data->openingHoursClosedDays as $index => $closedDayData) {
            // Skip entries with missing fields - schema validation will report these
            if (!isset($closedDayData->startDate, $closedDayData->endDate)) {
                continue;
            }

            try {
                $startDate = $this->parseDateTime($closedDayData->startDate);
                $endDate = $this->parseDateTime($closedDayData->endDate);
            } catch (\Throwable $e) {
                // Skip entries with malformed dates or invalid types - schema validation will report these
                continue;
            }

            if ($startDate > $endDate) {
                $errors[] = new SchemaError(
                    '/openingHoursClosedDays/' . $index . '/endDate',
                    'endDate should not be before startDate'
                );
            }

            // For periodic calendars, validate that closed days are within the periodic range
            if (isset($data->calendarType) && $data->calendarType === 'periodic' && isset($data->startDate, $data->endDate)) {
                try {
                    $periodicStart = $this->parseDateTime($data->startDate);
                    $periodicEnd = $this->parseDateTime($data->endDate);
                } catch (\Throwable $e) {
                    // Skip - calendar dates should be validated by DateRangeValidator
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
        $dateOnly = DateTimeImmutable::createFromFormat('Y-m-d|', $dateString, new DateTimeZone('UTC'));
        if ($dateOnly instanceof DateTimeImmutable) {
            return $dateOnly;
        }

        return DateTimeFactory::fromISO8601($dateString);
    }
}
