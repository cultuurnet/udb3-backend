<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

final class SubEvent
{
    private DateRange $dateRange;

    private Status $status;

    private BookingAvailabilityType $bookingAvailability;

    public function __construct(DateRange $dateRange, Status $status, BookingAvailabilityType $bookingAvailability)
    {
        $this->dateRange = $dateRange;
        $this->status = $status;
        $this->bookingAvailability = $bookingAvailability;
    }

    public function getDateRange(): DateRange
    {
        return $this->dateRange;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function getBookingAvailability(): BookingAvailabilityType
    {
        return $this->bookingAvailability;
    }
}
