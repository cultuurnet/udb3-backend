<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Clock;

use DateTimeInterface;

class FrozenClock implements Clock
{
    /**
     * @var DateTimeInterface
     */
    private $time;

    public function __construct(DateTimeInterface $dateTime)
    {
        $this->setTime($dateTime);
    }

    protected function setTime(DateTimeInterface $dateTime): void
    {
        $this->time = $dateTime;
    }

    public function getDateTime(): DateTimeInterface
    {
        return $this->time;
    }
}
