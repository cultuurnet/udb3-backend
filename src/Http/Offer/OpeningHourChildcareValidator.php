<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Time;
use CultuurNet\UDB3\Model\ValueObject\TimeImmutableRange;

/**
 * Validates childcare times against opening hours.
 *
 * Note: The JSON schema already validates time format (H:MM/HH:MM) via regex pattern "^\d?\d:\d\d$".
 * If parsing exceptions occur during validation, this indicates a schema validation bypass or broken
 * validation pipeline. Such exceptions should be logged to Sentry as they represent a system integrity issue.
 */
final class OpeningHourChildcareValidator
{
    /**
     * @return SchemaError[]
     */
    public function validate(object $data): array
    {
        if (!isset($data->openingHours) || !is_array($data->openingHours)) {
            // Error(s) will be reported by the Schema validation.
            return [];
        }

        $errors = [];
        foreach ($data->openingHours as $index => $openingHourData) {
            if (is_object($openingHourData)) {
                array_push($errors, ...$this->validateOpeningHours($openingHourData, '/openingHours/' . $index));
            }
        }
        return $errors;
    }

    /**
     * @return SchemaError[]
     */
    private function validateOpeningHours(object $openingHourData, string $jsonPointer): array
    {
        if (!isset($openingHourData->childcare) || !is_object($openingHourData->childcare)) {
            return [];
        }

        $childcare = $openingHourData->childcare;

        $start = isset($childcare->start) ? Time::fromString($childcare->start) : null;
        $end = isset($childcare->end) ? Time::fromString($childcare->end) : null;

        try {
            $dateRange = new TimeImmutableRange($start, $end);
        } catch (\InvalidArgumentException $e) {
            return [new SchemaError($jsonPointer . '/childcare', $e->getMessage())];
        }

        $opens = isset($openingHourData->opens) ? Time::fromString($openingHourData->opens) : null;
        $closes = isset($openingHourData->closes) ? Time::fromString($openingHourData->closes) : null;
        $errors = [];

        if ($opens !== null && !$dateRange->startIsBeforeTime($opens)) {
            $errors[] = new SchemaError($jsonPointer . '/childcare/start', 'childcare.start must be before opens');
        }
        if ($closes !== null && !$dateRange->endIsAfterTime($closes)) {
            $errors[] = new SchemaError($jsonPointer . '/childcare/end', 'childcare.end must be after closes');
        }

        return $errors;
    }
}
