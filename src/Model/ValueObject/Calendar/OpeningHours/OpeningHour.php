<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours;

class OpeningHour
{
    /**
     * @var Days
     */
    private $days;

    /**
     * @var Time
     */
    private $openingTime;

    /**
     * @var Time
     */
    private $closingTime;


    public function __construct(Days $days, Time $openingTime, Time $closingTime)
    {
        $this->days = $days;
        $this->openingTime = $openingTime;
        $this->closingTime = $closingTime;
    }

    /**
     * @return Days
     */
    public function getDays()
    {
        return $this->days;
    }

    /**
     * @return Time
     */
    public function getOpeningTime()
    {
        return $this->openingTime;
    }

    /**
     * @return Time
     */
    public function getClosingTime()
    {
        return $this->closingTime;
    }
}
