<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Model\ValueObject\Calendar\AdjustedOpeningHours;
use InvalidArgumentException;

/**
 * Validates adjusted opening hours for periodic and permanent calendars.
 *
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

            // Validate using AdjustedOpeningHours value object (validates startDate <= endDate)
            try {
                new AdjustedOpeningHours($startDate, $endDate, new \CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours());
            } catch (InvalidArgumentException) {
                $errors[] = new SchemaError(
                    '/openingHoursAdjusted/' . $index . '/endDate',
                    'endDate should not be before startDate'
                );
                continue;
            }

            // For periodic calendars, validate that adjusted opening hours are within the periodic range
            if (isset($data->calendarType, $data->startDate, $data->endDate) && $data->calendarType === 'periodic') {
                $periodicStart = DateTimeFactory::fromDateOrISO8601($data->startDate);
                $periodicEnd = DateTimeFactory::fromDateOrISO8601($data->endDate);

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

        for ($i = 1; $i < count($parsedEntries); $i++) {
            if ($parsedEntries[$i]['startDate'] <= $parsedEntries[$i - 1]['endDate']) {
                $errors[] = new SchemaError(
                    '/openingHoursAdjusted/' . $parsedEntries[$i]['index'] . '/startDate',
                    'adjusted opening hours entries must not overlap'
                );
            }
        }

        return $errors;
    }
}
