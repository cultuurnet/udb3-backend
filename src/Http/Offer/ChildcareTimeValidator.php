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
            $error = $this->validateStartTime($childcare->start, $data->startDate, $jsonPointer);
            if ($error !== null) {
                $errors[] = $error;
            }
        }

        if (isset($childcare->end, $data->endDate)
            && is_string($childcare->end)
            && is_string($data->endDate)) {
            $error = $this->validateEndTime($childcare->end, $data->endDate, $jsonPointer);
            if ($error !== null) {
                $errors[] = $error;
            }
        }

        return $errors;
    }

    private function validateStartTime(string $start, string $startDate, string $jsonPointer): ?SchemaError
    {
        try {
            $time = new Time($start);
        } catch (InvalidArgumentException $e) {
            return new SchemaError($jsonPointer . '/childcare/start', $e->getMessage());
        }

        try {
            $startDateTime = new DateTimeImmutable($startDate);
        } catch (Exception) {
            return null;
        }

        if ($time->toMinutes() >= $this->dateTimeToMinutes($startDateTime)) {
            return new SchemaError(
                $jsonPointer . '/childcare/start',
                'childcare.start must be before the time portion of startDate'
            );
        }

        return null;
    }

    private function validateEndTime(string $end, string $endDate, string $jsonPointer): ?SchemaError
    {
        try {
            $time = new Time($end);
        } catch (InvalidArgumentException $e) {
            return new SchemaError($jsonPointer . '/childcare/end', $e->getMessage());
        }

        try {
            $endDateTime = new DateTimeImmutable($endDate);
        } catch (Exception) {
            return null;
        }

        if ($time->toMinutes() <= $this->dateTimeToMinutes($endDateTime)) {
            return new SchemaError(
                $jsonPointer . '/childcare/end',
                'childcare.end must be after the time portion of endDate'
            );
        }

        return null;
    }

    private function dateTimeToMinutes(DateTimeImmutable $dateTime): int
    {
        return (int) $dateTime->format('H') * 60 + (int) $dateTime->format('i');
    }
}
