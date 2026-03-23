<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

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
            $startDate = new DateTimeImmutable($closedDayData->startDate);
            $endDate = new DateTimeImmutable($closedDayData->endDate);

            if ($startDate > $endDate) {
                $errors[] = new SchemaError(
                    '/openingHoursClosedDays/' . $index . '/endDate',
                    'endDate should not be before startDate'
                );
            }

            // For periodic calendars, validate that closed days are within the periodic range
            if (isset($data->calendarType) && $data->calendarType === 'periodic') {
                $periodicStart = isset($data->startDate) ? new DateTimeImmutable($data->startDate) : null;
                $periodicEnd = isset($data->endDate) ? new DateTimeImmutable($data->endDate) : null;

                if ($periodicStart !== null && $startDate < $periodicStart) {
                    $errors[] = new SchemaError(
                        '/openingHoursClosedDays/' . $index . '/startDate',
                        'startDate should not be before the calendar startDate'
                    );
                }

                if ($periodicEnd !== null && $endDate > $periodicEnd) {
                    $errors[] = new SchemaError(
                        '/openingHoursClosedDays/' . $index . '/endDate',
                        'endDate should not be after the calendar endDate'
                    );
                }
            }
        }

        return $errors;
    }
}
