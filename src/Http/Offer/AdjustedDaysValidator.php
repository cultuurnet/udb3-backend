<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\AdjustedDay;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Day;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Days;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Exception\StartDateAfterEndDate;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHour;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Time;

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

            $openingHourObjects = [];
            $hasTimeErrors = false;

            foreach ($adjustedOpeningHoursData->openingHours ?? [] as $ohIndex => $openingHour) {
                $opensTime = null;
                $closesTime = null;

                try {
                    $opensTime = Time::fromString($openingHour->opens);
                } catch (\Exception) {
                    $hasTimeErrors = true;
                    $errors[] = new SchemaError(
                        '/openingHoursAdjustedDays/' . $index . '/openingHours/' . $ohIndex . '/opens',
                        'Invalid time format (hh:mm)'
                    );
                }

                try {
                    $closesTime = Time::fromString($openingHour->closes);
                } catch (\Exception) {
                    $hasTimeErrors = true;
                    $errors[] = new SchemaError(
                        '/openingHoursAdjustedDays/' . $index . '/openingHours/' . $ohIndex . '/closes',
                        'Invalid time format (hh:mm)'
                    );
                }


                // Missing fields are not flagged here — the JSON schema enforces their presence.
                if ($opensTime !== null && $closesTime !== null) {
                    $days = new Days(...array_map(fn ($day) => new Day($day), (array)($openingHour->dayOfWeek ?? [])));
                    $openingHourObjects[] = new OpeningHour($days, $opensTime, $closesTime);
                }
            }

            if ($hasTimeErrors) {
                continue;
            }

            try {
                new AdjustedDay($startDate, $endDate, new OpeningHours(...$openingHourObjects), null);
            } catch (StartDateAfterEndDate $e) {
                $errors[] = new SchemaError(
                    '/openingHoursAdjustedDays/' . $index . '/endDate',
                    $e->getMessage()
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
