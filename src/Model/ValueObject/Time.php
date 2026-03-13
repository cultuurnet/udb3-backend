<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject;

use InvalidArgumentException;

final class Time
{
    private string $value;

    public function __construct(string $value)
    {
        $this->guardTime($value);
        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function toMinutes(): int
    {
        [$hour, $minutes] = explode(':', $this->value);
        return (int) $hour * 60 + (int) $minutes;
    }

    private function guardTime(string $time): void
    {
        if (!preg_match('/^\d?\d:\d\d$/', $time)) {
            throw new InvalidArgumentException(
                sprintf('"%s" is not a valid time. Expected format is H:MM or HH:MM.', $time)
            );
        }

        [$hour, $minutes] = explode(':', $time);

        if ((int) $hour < 0 || (int) $hour > 24) {
            throw new InvalidArgumentException(
                sprintf('"%s" is not a valid time. Hour must be between 0 and 24.', $time)
            );
        }

        if ((int) $hour === 24 && (int) $minutes !== 0) {
            throw new InvalidArgumentException(
                sprintf('"%s" is not a valid time. When hour is 24, minutes must be 0.', $time)
            );
        }

        if ((int) $minutes < 0 || (int) $minutes > 59) {
            throw new InvalidArgumentException(
                sprintf('"%s" is not a valid time. Minutes must be between 0 and 59.', $time)
            );
        }
    }
}
