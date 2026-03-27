<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Model\ValueObject\Calendar\ClosedDay;
use InvalidArgumentException;

/**
 * Validates closed days for periodic and permanent calendars.
 *
 * Note: The JSON schema already validates date format (Y-m-d or ISO8601) via "format" and pattern rules.
 * Domain validation (startDate <= endDate) is delegated to ClosedDay value object.
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
            $startDate = DateTimeFactory::fromDateOrISO8601($closedDayData->startDate);
            $endDate = DateTimeFactory::fromDateOrISO8601($closedDayData->endDate);

            // Validate using ClosedDay value object (validates startDate <= endDate)
            try {
                new ClosedDay($startDate, $endDate);
            } catch (InvalidArgumentException) {
                $errors[] = new SchemaError(
                    '/openingHoursClosedDays/' . $index . '/endDate',
                    'endDate should not be before startDate'
                );
                continue;
            }

            // For periodic calendars, validate that closed days are within the periodic range
            if (isset($data->calendarType, $data->startDate, $data->endDate) && $data->calendarType === 'periodic') {
                $periodicStart = DateTimeFactory::fromDateOrISO8601($data->startDate);
                $periodicEnd = DateTimeFactory::fromDateOrISO8601($data->endDate);

                if ($startDate < $periodicStart) {
                    $errors[] = new SchemaError(
                        '/openingHoursClosedDays/' . $index . '/startDate',
                        'the start date of a closed day should not be before the calendar start date'
                    );
                }

                if ($endDate > $periodicEnd) {
                    $errors[] = new SchemaError(
                        '/openingHoursClosedDays/' . $index . '/endDate',
                        'the end date of a closed day should not be after the calendar end date'
                    );
                }
            }
        }

        return $errors;
    }
}
