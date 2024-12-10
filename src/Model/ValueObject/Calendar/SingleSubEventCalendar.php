<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

class SingleSubEventCalendar implements CalendarWithDateRange, CalendarWithSubEvents
{
    private SubEvent $subEvent;

    private Status $status;

    private BookingAvailability $bookingAvailability;

    public function __construct(SubEvent $subEvent)
    {
        $this->subEvent = $subEvent;
        $this->status = new Status(StatusType::Available());
        $this->bookingAvailability = new BookingAvailability(BookingAvailabilityType::Available());
    }

    public function withStatus(Status $status): Calendar
    {
        $clone = clone $this;
        $clone->status = $status;
        return $clone;
    }

    public function withStatusOnSubEvents(Status $status): self
    {
        $clone = clone $this;
        $clone->subEvent = $this->subEvent->withStatus($status);
        return $clone;
    }

    public function withBookingAvailability(BookingAvailability $bookingAvailability): Calendar
    {
        $clone = clone $this;
        $clone->bookingAvailability = $bookingAvailability;
        return $clone;
    }

    public function withBookingAvailabilityOnSubEvents(BookingAvailability $bookingAvailability): CalendarWithSubEvents
    {
        $clone = clone $this;
        $clone->subEvent = $this->subEvent->withBookingAvailability($bookingAvailability);
        return $clone;
    }

    public function getType(): CalendarType
    {
        return CalendarType::single();
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function getBookingAvailability(): BookingAvailability
    {
        return $this->bookingAvailability;
    }

    public function getStartDate(): \DateTimeImmutable
    {
        return $this->subEvent->getDateRange()->getFrom();
    }

    public function getEndDate(): \DateTimeImmutable
    {
        return $this->subEvent->getDateRange()->getTo();
    }

    public function getSubEvents(): SubEvents
    {
        return new SubEvents($this->subEvent);
    }
}
