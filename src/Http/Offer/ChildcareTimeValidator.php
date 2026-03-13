<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Model\ValueObject\Time;
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

        $childcare = $data->childcare ?? null;
        if (!is_object($childcare)) {
            return $errors;
        }

        if (isset($childcare->start, $data->startDate)
            && is_string($childcare->start)
            && is_string($data->startDate)) {
            $error = $this->validateChildcareTime(
                'start',
                $childcare->start,
                $data->startDate,
                'childcare.start must be before the time portion of startDate',
                $jsonPointer
            );
            if ($error !== null) {
                $errors[] = $error;
            }
        }

        if (isset($childcare->end, $data->endDate)
            && is_string($childcare->end)
            && is_string($data->endDate)) {
            $error = $this->validateChildcareTime(
                'end',
                $childcare->end,
                $data->endDate,
                'childcare.end must be after the time portion of endDate',
                $jsonPointer,
                isEnd: true
            );
            if ($error !== null) {
                $errors[] = $error;
            }
        }

        return $errors;
    }

    private function validateChildcareTime(
        string $field,
        string $time,
        string $date,
        string $errorMessage,
        string $jsonPointer,
        bool $isEnd = false
    ): ?SchemaError {
        try {
            $childcareTime = new Time($time);
        } catch (InvalidArgumentException $e) {
            return new SchemaError($jsonPointer . '/childcare/' . $field, $e->getMessage());
        }

        try {
            $dateTime = new DateTimeImmutable($date);
        } catch (Exception) {
            return null;
        }

        $childcareMinutes = $childcareTime->toMinutes();
        $dateMinutes = $this->dateTimeToMinutes($dateTime);

        // For start: must be before (childcareMinutes < dateMinutes)
        // For end: must be after (childcareMinutes > dateMinutes)
        if ($isEnd ? $childcareMinutes <= $dateMinutes : $childcareMinutes >= $dateMinutes) {
            return new SchemaError($jsonPointer . '/childcare/' . $field, $errorMessage);
        }

        return null;
    }

    private function dateTimeToMinutes(DateTimeImmutable $dateTime): int
    {
        return (int) $dateTime->format('H') * 60 + (int) $dateTime->format('i');
    }
}
