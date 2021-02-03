<?php
/**
 * @file
 */

namespace CultuurNet\Clock;

use DateTimeInterface;

/**
 * Clock of which the time has frozen.
 */
class FrozenClock implements Clock
{
    /**
     * @var \DateTimeInterface
     */
    private $time;

    public function __construct(DateTimeInterface $dateTime)
    {
        $this->setTime($dateTime);
    }

    protected function setTime(DateTimeInterface $dateTime) {
        $this->time = $dateTime;
    }

    /**
     * @return DateTimeInterface
     */
    public function getDateTime()
    {
        return $this->time;
    }
}
