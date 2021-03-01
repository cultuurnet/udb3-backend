<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

class MultipleSubEventsCalendar implements CalendarWithDateRange, CalendarWithSubEvents
{
    /**
     * @var SubEvents
     */
    private $dateRanges;

    /**
     * @var Status
     */
    private $status;

    public function __construct(SubEvents $dateRanges)
    {
        if ($dateRanges->getLength() < 2) {
            throw new \InvalidArgumentException('Multiple date ranges calendar requires at least 2 date ranges.');
        }

        $this->dateRanges = $dateRanges;
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
        return CalendarType::multiple();
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function getStartDate(): \DateTimeImmutable
    {
        return $this->dateRanges->getStartDate();
    }

    public function getEndDate(): \DateTimeImmutable
    {
        return $this->dateRanges->getEndDate();
    }

    public function getSubEvents(): SubEvents
    {
        return $this->dateRanges;
    }
}
