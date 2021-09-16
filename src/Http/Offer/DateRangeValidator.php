<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use DateTimeImmutable;

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

        $startDate = DateTimeImmutable::createFromFormat(DATE_ATOM, $data->startDate);
        $endDate = DateTimeImmutable::createFromFormat(DATE_ATOM, $data->endDate);
        if ($startDate === false || $endDate === false) {
            // Error(s) will be reported by the Schema validation.
            return [];
        }

        if ($startDate > $endDate) {
            return [new SchemaError($jsonPointer . '/endDate', 'endDate should not be before startDate')];
        }
        return [];
    }
}
