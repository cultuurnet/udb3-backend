<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use RuntimeException;

final class RemainingCapacityExceedsCapacity extends RuntimeException
{
    private const ERROR_MESSAGE = 'remainingCapacity must be less than or equal to capacity';

    private string $jsonPointer;

    public function __construct(string $jsonPointer = '/bookingAvailability/remainingCapacity')
    {
        $this->jsonPointer = $jsonPointer;
        parent::__construct("{$jsonPointer}: " . self::ERROR_MESSAGE);
    }

    public function getJsonPointer(): string
    {
        return $this->jsonPointer;
    }

    public function getErrorMessage(): string
    {
        return self::ERROR_MESSAGE;
    }
}
