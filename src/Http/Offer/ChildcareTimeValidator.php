<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Model\ValueObject\TimeImmutableRange;
use DateTimeImmutable;
use Exception;
use InvalidArgumentException;

final class ChildcareTimeValidator
{
    /**
     * @return SchemaError[]
     */
    public function validate(object $data, string $jsonPointer = ''): array
    {
        $errors = [];

        if (isset($data->childcareStartTime, $data->startDate)
            && is_string($data->childcareStartTime)
            && is_string($data->startDate)) {
            $error = $this->validateStartTime($data->childcareStartTime, $data->startDate, $jsonPointer);
            if ($error !== null) {
                $errors[] = $error;
            }
        }

        if (isset($data->childcareEndTime, $data->endDate)
            && is_string($data->childcareEndTime)
            && is_string($data->endDate)) {
            $error = $this->validateEndTime($data->childcareEndTime, $data->endDate, $jsonPointer);
            if ($error !== null) {
                $errors[] = $error;
            }
        }

        return $errors;
    }

    private function validateStartTime(string $childcareStartTime, string $startDate, string $jsonPointer): ?SchemaError
    {
        try {
            $range = new TimeImmutableRange($childcareStartTime);
        } catch (InvalidArgumentException $e) {
            return new SchemaError($jsonPointer . '/childcareStartTime', $e->getMessage());
        }

        try {
            $startDateTime = new DateTimeImmutable($startDate);
        } catch (Exception) {
            return null;
        }

        if (!$range->startIsBeforeTimeOf($startDateTime)) {
            return new SchemaError(
                $jsonPointer . '/childcareStartTime',
                'childcareStartTime must be before the time portion of startDate'
            );
        }

        return null;
    }

    private function validateEndTime(string $childcareEndTime, string $endDate, string $jsonPointer): ?SchemaError
    {
        try {
            $range = new TimeImmutableRange(null, $childcareEndTime);
        } catch (InvalidArgumentException $e) {
            return new SchemaError($jsonPointer . '/childcareEndTime', $e->getMessage());
        }

        try {
            $endDateTime = new DateTimeImmutable($endDate);
        } catch (Exception) {
            return null;
        }

        if (!$range->endIsAfterTimeOf($endDateTime)) {
            return new SchemaError(
                $jsonPointer . '/childcareEndTime',
                'childcareEndTime must be after the time portion of endDate'
            );
        }

        return null;
    }
}
