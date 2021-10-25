<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours;

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
}
