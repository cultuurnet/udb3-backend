<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\DateTimeInvalid;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;

class DateRangeValidator
{
    /**
     * @return SchemaError[]
     */
    public function validate(object $data, string $jsonPointer = ''): array
    {
        if (!isset($data->startDate, $data->endDate) || !is_string($data->startDate) || !is_string($data->endDate)) {
            // Error(s) will be reported by the Schema validation.
            return [];
        }

        try {
            $startDate = DateTimeFactory::fromISO8601($data->startDate);
            $endDate = DateTimeFactory::fromISO8601($data->endDate);
        } catch (DateTimeInvalid $e) {
            // Date format error(s) will be reported by the Schema validation.
            return [];
        }

        if ($startDate > $endDate) {
            return [new SchemaError($jsonPointer . '/endDate', 'endDate should not be before startDate')];
        }
        return [];
    }
}
