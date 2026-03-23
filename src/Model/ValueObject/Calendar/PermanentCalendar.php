<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;

class PermanentCalendar implements CalendarWithOpeningHours, CalendarWithClosedDays
{
    private OpeningHours $openingHours;

    private ClosedDays $closedDays;

    private Status $status;

    private BookingAvailability $bookingAvailability;

    public function __construct(OpeningHours $openingHours)
    {
        $this->openingHours = $openingHours;
        $this->closedDays = new ClosedDays();
        $this->status = new Status(StatusType::Available());
        $this->bookingAvailability = new BookingAvailability(BookingAvailabilityType::Available());
    }

    public function withStatus(Status $status): Calendar
    {
        $clone = clone $this;
        $clone->status = $status;
        return $clone;
    }

    public function withBookingAvailability(BookingAvailability $bookingAvailability): Calendar
    {
        $clone = clone $this;
        $clone->bookingAvailability = $bookingAvailability;
        return $clone;
    }

    public function getType(): CalendarType
    {
        return CalendarType::permanent();
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function getBookingAvailability(): BookingAvailability
    {
        return $this->bookingAvailability;
    }

    public function getOpeningHours(): OpeningHours
    {
        return $this->openingHours;
    }

    public function getClosedDays(): ClosedDays
    {
        return $this->closedDays;
    }

    public function withClosedDays(ClosedDays $closedDays): static
    {
        $clone = clone $this;
        $clone->closedDays = $closedDays;
        return $clone;
    }
}
