<?php

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

    /**
     * @param Days $days
     * @param Time $openingTime
     * @param Time $closingTime
     */
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
