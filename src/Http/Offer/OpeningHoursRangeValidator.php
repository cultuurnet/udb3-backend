<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use DateTimeImmutable;

final class OpeningHoursRangeValidator
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
            if (!isset($openingHourData->opens, $openingHourData->closes) ||
                !is_string($openingHourData->opens) ||
                !is_string($openingHourData->closes)) {
                // Error(s) will be reported by the Schema validation.
                continue;
            }

            $opens = DateTimeImmutable::createFromFormat('H:i', $openingHourData->opens);
            $closes = DateTimeImmutable::createFromFormat('H:i', $openingHourData->closes);

            if ($opens !== false && $closes !== false && $opens > $closes) {
                $errors[] = new SchemaError('/openingHours/' . $index . '/closes', 'closes should not be before opens');
            }
        }
        return $errors;
    }
}
