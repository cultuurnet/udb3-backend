<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Time;
use CultuurNet\UDB3\Model\ValueObject\TimeImmutableRange;
use DateTimeImmutable;

/**
 * Validates childcare times against event dates.
 *
 * Note: The JSON schema already validates datetime format (RFC 3339) via "format": "date-time"
 * and time format (H:MM/HH:MM) via regex pattern "^([01]?\d|2[0-3]):[0-5]\d$". If parsing exceptions occur
 * during validation, this indicates a schema validation bypass or broken validation pipeline.
 * Such exceptions should be logged to Sentry as they represent a system integrity issue.
 */
final class ChildcareTimeValidator
{
    /**
     * @return SchemaError[]
     */
    public function validate(object $data, string $jsonPointer = ''): array
    {
        if (!isset($data->childcare) || !is_object($data->childcare)) {
            return [];
        }

        $childcare = $data->childcare;

        $start = isset($childcare->start) ? Time::fromString($childcare->start) : null;
        $end = isset($childcare->end) ? Time::fromString($childcare->end) : null;

        try {
            $dateRange = new TimeImmutableRange($start, $end);
        } catch (\InvalidArgumentException $e) {
            return [new SchemaError($jsonPointer . '/childcare', $e->getMessage())];
        }

        $startDate = isset($data->startDate) ? new DateTimeImmutable($data->startDate) : null;
        $endDate = isset($data->endDate) ? new DateTimeImmutable($data->endDate) : null;
        $errors = [];

        if ($startDate !== null && !$dateRange->startIsBeforeTimeOf($startDate)) {
            $errors[] = new SchemaError($jsonPointer . '/childcare/start', 'childcare.start must be before the time portion of startDate');
        }
        if ($endDate !== null && !$dateRange->endIsAfterTimeOf($endDate)) {
            $errors[] = new SchemaError($jsonPointer . '/childcare/end', 'childcare.end must be after the time portion of endDate');
        }

        return $errors;
    }
}
