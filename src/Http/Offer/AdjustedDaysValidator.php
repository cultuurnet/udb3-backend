<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;

/**
 * Note: The JSON schema already validates date format (Y-m-d or ISO8601) via "format" and pattern rules.
 * Domain validation (startDate <= endDate) is delegated to the AdjustedDay value object.
 */
final class AdjustedDaysValidator
{
    /**
     * @return SchemaError[]
     */
    public function validate(object $data): array
    {
        if (!isset($data->openingHoursAdjustedDays) || !is_array($data->openingHoursAdjustedDays)) {
            return [];
        }

        $errors = [];
        $parsedEntries = [];

        foreach ($data->openingHoursAdjustedDays as $index => $adjustedOpeningHoursData) {
            $startDate = DateTimeFactory::fromDateOrISO8601($adjustedOpeningHoursData->startDate);
            $endDate = DateTimeFactory::fromDateOrISO8601($adjustedOpeningHoursData->endDate);

            if ($startDate > $endDate) {
                $errors[] = new SchemaError(
                    '/openingHoursAdjustedDays/' . $index . '/endDate',
                    'startDate should not be later than endDate'
                );
                continue;
            }

            // For periodic calendars, validate that the adjusted opening hours entry starts within the periodic range.
            // Parse calendar dates by their date portion only (in Europe/Brussels) to avoid timezone mismatches
            // when comparing date-only entry dates against ISO8601 calendar dates.
            if (isset($data->calendarType, $data->startDate, $data->endDate) && $data->calendarType === 'periodic') {
                $periodicStart = DateTimeFactory::fromDateOrISO8601(substr($data->startDate, 0, 10));
                $periodicEnd = DateTimeFactory::fromDateOrISO8601(substr($data->endDate, 0, 10));

                if ($startDate < $periodicStart) {
                    $errors[] = new SchemaError(
                        '/openingHoursAdjustedDays/' . $index . '/startDate',
                        'the start date of adjusted opening hours should not be before the calendar start date'
                    );
                }

                if ($endDate > $periodicEnd) {
                    $errors[] = new SchemaError(
                        '/openingHoursAdjustedDays/' . $index . '/endDate',
                        'the end date of adjusted opening hours should not be after the calendar end date'
                    );
                }
            }

            $parsedEntries[] = [
                'index' => $index,
                'startDate' => $startDate,
                'endDate' => $endDate,
            ];
        }

        $this->checkForOverlaps($parsedEntries, $errors);

        return $errors;
    }

    public function checkForOverlaps(array $parsedEntries, array &$errors): array
    {
        usort(
            $parsedEntries,
            fn ($a, $b) => $a['startDate'] <=> $b['startDate']
        );

        for ($i = 1, $iMax = count($parsedEntries); $i < $iMax; $i++) {
            if ($parsedEntries[$i]['startDate'] <= $parsedEntries[$i - 1]['endDate']) {
                $errors[] = new SchemaError(
                    '/openingHoursAdjustedDays/' . $parsedEntries[$i]['index'] . '/startDate',
                    'adjusted opening hours entries must not overlap'
                );
            }
        }

        return $errors;
    }
}
