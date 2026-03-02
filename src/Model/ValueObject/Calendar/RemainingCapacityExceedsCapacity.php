<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use RuntimeException;

final class RemainingCapacityExceedsCapacity extends RuntimeException
{
    public static function withValues(
        string $jsonPointer = '/bookingAvailability/remainingCapacity'
    ): self {
        $message = 'remainingCapacity must be less than or equal to capacity';

        return new self("{$jsonPointer}: {$message}");
    }

    public function getJsonPointer(): string
    {
        $parts = explode(': ', $this->message, 2);
        return $parts[0] ?? '/bookingAvailability/remainingCapacity';
    }

    public function getErrorMessage(): string
    {
        $parts = explode(': ', $this->message, 2);
        return $parts[1] ?? 'remainingCapacity must be less than or equal to capacity';
    }
}
