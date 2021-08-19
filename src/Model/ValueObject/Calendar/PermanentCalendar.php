<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;

class PermanentCalendar implements CalendarWithOpeningHours
{
    private OpeningHours $openingHours;

    private Status $status;

    private BookingAvailabilityType $bookingAvailability;

    public function __construct(OpeningHours $openingHours)
    {
        $this->openingHours = $openingHours;
        $this->status = new Status(StatusType::Available());
        $this->bookingAvailability = BookingAvailabilityType::Available();
    }

    public function withStatus(Status $status): Calendar
    {
        $clone = clone $this;
        $clone->status = $status;
        return $clone;
    }

    public function withBookingAvailability(BookingAvailabilityType $bookingAvailability): Calendar
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

    public function getBookingAvailability(): BookingAvailabilityType
    {
        return $this->bookingAvailability;
    }

    public function getOpeningHours(): OpeningHours
    {
        return $this->openingHours;
    }
}
