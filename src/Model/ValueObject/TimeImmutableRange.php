<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject;

use DateTimeImmutable;
use InvalidArgumentException;

final class TimeImmutableRange
{
    private ?Time $start;

    private ?Time $end;

    public function __construct(?Time $start = null, ?Time $end = null)
    {
        if ($start !== null && $end !== null && $start->toMinutes() >= $end->toMinutes()) {
            throw new InvalidArgumentException(
                sprintf('"%s" must be before "%s".', $start->getValue(), $end->getValue())
            );
        }

        $this->start = $start;
        $this->end = $end;
    }

    public function getStart(): ?Time
    {
        return $this->start;
    }

    public function getEnd(): ?Time
    {
        return $this->end;
    }

    public function startIsBeforeTimeOf(DateTimeImmutable $dateTime): bool
    {
        if ($this->start === null) {
            return true;
        }
        return $this->start->toMinutes() < $this->dateTimeToMinutes($dateTime);
    }

    public function endIsAfterTimeOf(DateTimeImmutable $dateTime): bool
    {
        if ($this->end === null) {
            return true;
        }
        return $this->end->toMinutes() > $this->dateTimeToMinutes($dateTime);
    }

    private function dateTimeToMinutes(DateTimeImmutable $dateTime): int
    {
        return (int) $dateTime->format('H') * 60 + (int) $dateTime->format('i');
    }
}
