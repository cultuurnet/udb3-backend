<?php

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;

class PeriodicCalendar implements CalendarWithDateRange, CalendarWithOpeningHours
{
    /**
     * @var DateRange
     */
    private $dateRange;

    /**
     * @var OpeningHours
     */
    private $openingHours;

    /**
     * @var Status
     */
    private $status;

    public function __construct(
        DateRange $dateRange,
        OpeningHours $openingHours
    ) {
        $this->dateRange = $dateRange;
        $this->openingHours = $openingHours;
        $this->status = new Status(StatusType::Available());
    }

    public function withStatus(Status $status): Calendar
    {
        $clone = clone $this;
        $clone->status = $status;
        return $clone;
    }

    public function getType(): CalendarType
    {
        return CalendarType::periodic();
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function getStartDate(): \DateTimeImmutable
    {
        return $this->dateRange->getFrom();
    }

    public function getEndDate(): \DateTimeImmutable
    {
        return $this->dateRange->getTo();
    }

    public function getOpeningHours(): OpeningHours
    {
        return $this->openingHours;
    }
}
