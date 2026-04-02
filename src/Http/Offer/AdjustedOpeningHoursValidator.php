<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Hour;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Minute;
use InvalidArgumentException;

/***
 * Note: The JSON schema already validates date format (Y-m-d or ISO8601) via "format" and pattern rules.
 * Domain validation (startDate <= endDate) is delegated to AdjustedOpeningHours value object.
 */
final class AdjustedOpeningHoursValidator
{
    /**
     * @return SchemaError[]
     */
    public function validate(object $data): array
    {
        if (!isset($data->openingHoursAdjusted) || !is_array($data->openingHoursAdjusted)) {
            return [];
        }

        $errors = [];
        $parsedEntries = [];

        foreach ($data->openingHoursAdjusted as $index => $adjustedOpeningHoursData) {
            $startDate = DateTimeFactory::fromDateOrISO8601($adjustedOpeningHoursData->startDate);
            $endDate = DateTimeFactory::fromDateOrISO8601($adjustedOpeningHoursData->endDate);

            foreach ($adjustedOpeningHoursData->openingHours ?? [] as $ohIndex => $openingHour) {
                $errors = $this->checkIfTimeIsValid('opens', $openingHour, $index, $ohIndex, $errors);
                $errors = $this->checkIfTimeIsValid('closes', $openingHour, $index, $ohIndex, $errors);
            }

            if ($startDate > $endDate) {
                $errors[] = new SchemaError(
                    '/openingHoursAdjusted/' . $index . '/endDate',
                    'endDate should not be before startDate'
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
                        '/openingHoursAdjusted/' . $index . '/startDate',
                        'the start date of adjusted opening hours should not be before the calendar start date'
                    );
                }

                if ($endDate > $periodicEnd) {
                    $errors[] = new SchemaError(
                        '/openingHoursAdjusted/' . $index . '/endDate',
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

        // Check for overlaps
        usort(
            $parsedEntries,
            fn ($a, $b) => $a['startDate'] <=> $b['startDate']
        );

        for ($i = 1, $iMax = count($parsedEntries); $i < $iMax; $i++) {
            if ($parsedEntries[$i]['startDate'] <= $parsedEntries[$i - 1]['endDate']) {
                $errors[] = new SchemaError(
                    '/openingHoursAdjusted/' . $parsedEntries[$i]['index'] . '/startDate',
                    'adjusted opening hours entries must not overlap'
                );
            }
        }

        return $errors;
    }

    private function checkIfTimeIsValid(string $field, object $openingHour, int|string $index, int|string $ohIndex, array $errors): array
    {
        $time = $openingHour->$field ?? null;
        if ($time !== null) {
            [$hours, $minutes] = explode(':', $time) + [0, 0];
            try {
                new Hour((int)$hours);
                new Minute((int)$minutes);
            } catch (InvalidArgumentException) {
                $errors[] = new SchemaError(
                    '/openingHoursAdjusted/' . $index . '/openingHours/' . $ohIndex . '/' . $field,
                    'Invalid time format (hh:mm)'
                );
            }
        }
        return $errors;
    }
}
