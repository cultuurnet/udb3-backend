<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject;

use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Time;
use DateTimeImmutable;
use DateTimeZone;
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

    public function startIsBeforeTime(Time $time): bool
    {
        if ($this->start === null) {
            return true;
        }
        return $this->start->toMinutes() < $time->toMinutes();
    }

    public function endIsAfterTime(Time $time): bool
    {
        if ($this->end === null) {
            return true;
        }
        return $this->end->toMinutes() > $time->toMinutes();
    }

    private function dateTimeToMinutes(DateTimeImmutable $dateTime): int
    {
        // We always handle HH:mm time information as Brussels local time. To compare it to a datetime, the datetime should also be converted to Europe/Brussels
        $local = $dateTime->setTimezone(new DateTimeZone('Europe/Brussels'));
        return (int) $local->format('H') * 60 + (int) $local->format('i');
    }
}
