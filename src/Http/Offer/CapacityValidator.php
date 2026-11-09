<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\ApiProblem\SchemaError;

/**
 * Capacity is only supported on single and multiple calendars, so this rejects it on periodic and permanent calendars.
 */
final class CapacityValidator
{
    /**
     * @return SchemaError[]
     */
    public function validate(object $data): array
    {
        if (!isset($data->subEvent) || !is_array($data->subEvent)) {
            return [];
        }

        $errors = [];
        foreach ($data->subEvent as $index => $subEvent) {
            if (is_object($subEvent) && isset($subEvent->capacity)) {
                $errors[] = new SchemaError(
                    '/subEvent/' . $index . '/capacity',
                    'capacity is only allowed when calendarType is single or multiple'
                );
            }
        }
        return $errors;
    }
}
