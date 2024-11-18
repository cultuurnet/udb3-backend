<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours;

use CultuurNet\UDB3\DateTimeFactory;
use DateTime;
use DateTimeInterface;

class Time
{
    private Hour $hour;

    private Minute $minute;

    public function __construct(Hour $hour, Minute $minute)
    {
        $this->hour = $hour;
        $this->minute = $minute;
    }

    public function getHour(): Hour
    {
        return $this->hour;
    }

    public function getMinute(): Minute
    {
        return $this->minute;
    }

    public static function fromDateTime(DateTimeInterface $dateTime): Time
    {
        return new Time(
            new Hour((int) ($dateTime->format('H'))),
            new Minute((int) ($dateTime->format('i')))
        );
    }

    public static function fromString(string $time): Time
    {
        return self::fromDateTime(DateTimeFactory::fromFormat('H:i', $time));
    }

    public function sameAs(Time $time): bool
    {
        return $this->getHour()->sameAs($time->getHour()) &&
            $this->getMinute()->sameAs($time->getMinute());
    }

    public function toString(): string
    {
        $hour = $this->getHour()->toInteger();
        $minute = $this->getMinute()->toInteger();

        $time = new DateTime('now');
        $time->setTime($hour, $minute);
        return $time->format('H:i');
    }
}
