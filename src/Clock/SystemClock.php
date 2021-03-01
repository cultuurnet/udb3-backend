<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Clock;

use DateTimeImmutable;
use DateTimeInterface;
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

    public function getDateTime(): DateTimeInterface
    {
        return new DateTimeImmutable('now', $this->timezone);
    }
}
