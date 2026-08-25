<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

class MultipleSubEventsCalendar implements CalendarWithDateRange, CalendarWithSubEvents
{
    private SubEvents $dateRanges;

    private Status $status;

    private BookingAvailability $bookingAvailability;

    public function __construct(SubEvents $dateRanges)
    {
        if ($dateRanges->getLength() < 2) {
            throw new \InvalidArgumentException('Multiple date ranges calendar requires at least 2 date ranges.');
        }

        $this->dateRanges = $dateRanges;
        $this->status = new Status(StatusType::Available());
        $this->bookingAvailability = new BookingAvailability(BookingAvailabilityType::Available());
    }

    public function withStatus(Status $status): Calendar
    {
        $clone = clone $this;
        $clone->status = $status;
        return $clone;
    }

    public function withStatusOnSubEvents(Status $status): CalendarWithSubEvents
    {
        $clone = clone $this;

        $clone->dateRanges = new SubEvents(...\array_map(
            function (SubEvent $subEvent) use ($status): SubEvent {
                return $subEvent->withStatus($status);
            },
            $clone->getSubEvents()->toArray()
        ));

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

        $clone->dateRanges = new SubEvents(...\array_map(
            function (SubEvent $subEvent) use ($bookingAvailability): SubEvent {
                return $subEvent->withBookingAvailability($bookingAvailability);
            },
            $clone->getSubEvents()->toArray()
        ));

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

    public function getBookingAvailability(): BookingAvailability
    {
        return $this->bookingAvailability;
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
