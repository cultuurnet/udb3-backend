<?php

namespace CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours;

class Time
{
    /**
     * @var Hour
     */
    private $hour;

    /**
     * @var Minute
     */
    private $minute;

    /**
     * @param Hour $hour
     * @param Minute $minute
     */
    public function __construct(Hour $hour, Minute $minute)
    {
        $this->hour = $hour;
        $this->minute = $minute;
    }

    /**
     * @return Hour
     */
    public function getHour()
    {
        return $this->hour;
    }

    /**
     * @return Minute
     */
    public function getMinute()
    {
        return $this->minute;
    }
}
