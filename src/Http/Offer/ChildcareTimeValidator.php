<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Model\ValueObject\Time;
use CultuurNet\UDB3\Model\ValueObject\TimeImmutableRange;
use DateTimeImmutable;

final class ChildcareTimeValidator
{
    /**
     * @return SchemaError[]
     */
    public function validate(object $data, string $jsonPointer = ''): array
    {
        $errors = [];

        if(!isset($data->childcare)) {
            return [];
        }

        try {
            $dateRange = new TimeImmutableRange(new Time($data->childcare->start), new Time($data->childcare->end));
        } catch (\Throwable $e) {
            return [new SchemaError($jsonPointer . '/childcare', $e->getMessage())];
        }

        try {
            $dateRange->startIsBeforeTimeOf(new DateTimeImmutable($data->childcare->startDate));
        } catch (\Throwable $e) {
            $errors[] = new SchemaError($jsonPointer . '/childcare/start', $e->getMessage());
        }

        try {
            $dateRange->endIsAfterTimeOf(new DateTimeImmutable($data->childcare->endDate));
        } catch (\Throwable $e) {
            $errors[] = new SchemaError($jsonPointer . '/childcare/end', $e->getMessage());
        }

        return $errors;
    }
}
