<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours;

class OpeningHour
{
    private Days $days;

    private Time $openingTime;

    private Time $closingTime;

    public function __construct(Days $days, Time $openingTime, Time $closingTime)
    {
        $this->days = $days;
        $this->openingTime = $openingTime;
        $this->closingTime = $closingTime;
    }

    public function getDays(): Days
    {
        return $this->days;
    }

    public function getOpeningTime(): Time
    {
        return $this->openingTime;
    }

    public function getClosingTime(): Time
    {
        return $this->closingTime;
    }
}
