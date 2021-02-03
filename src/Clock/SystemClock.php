<?php
/**
 * @file
 */

namespace CultuurNet\Clock;

use DateTimeZone;

/**
 * Clock using the actual time on the system.
 */
class SystemClock implements Clock
{
    private $timezone;

    public function __construct(DateTimeZone $timezone)
    {
        $this->timezone = $timezone;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getDateTime()
    {
        return new \DateTimeImmutable('now', $this->timezone);
    }
}
