<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject;

use DateTimeImmutable;
use InvalidArgumentException;

final class TimeImmutableRange
{
    private ?string $start;

    private ?string $end;

    public function __construct(?string $start = null, ?string $end = null)
    {
        if ($start !== null) {
            $this->guardTime($start);
        }

        if ($end !== null) {
            $this->guardTime($end);
        }

        if ($start !== null && $end !== null && $this->toMinutes($start) >= $this->toMinutes($end)) {
            throw new InvalidArgumentException(
                sprintf('"%s" must be before "%s".', $start, $end)
            );
        }

        $this->start = $start;
        $this->end = $end;
    }

    public function getStart(): ?string
    {
        return $this->start;
    }

    public function getEnd(): ?string
    {
        return $this->end;
    }

    public function startIsBeforeTimeOf(DateTimeImmutable $dateTime): bool
    {
        if ($this->start === null) {
            return true;
        }
        return $this->toMinutes($this->start) < $this->dateTimeToMinutes($dateTime);
    }

    public function endIsAfterTimeOf(DateTimeImmutable $dateTime): bool
    {
        if ($this->end === null) {
            return true;
        }
        return $this->toMinutes($this->end) > $this->dateTimeToMinutes($dateTime);
    }

    private function dateTimeToMinutes(DateTimeImmutable $dateTime): int
    {
        return (int) $dateTime->format('H') * 60 + (int) $dateTime->format('i');
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

        if ((int) $minutes < 0 || (int) $minutes > 59) {
            throw new InvalidArgumentException(
                sprintf('"%s" is not a valid time. Minutes must be between 0 and 59.', $time)
            );
        }
    }

    private function toMinutes(string $time): int
    {
        [$hour, $minutes] = explode(':', $time);
        return (int) $hour * 60 + (int) $minutes;
    }
}
