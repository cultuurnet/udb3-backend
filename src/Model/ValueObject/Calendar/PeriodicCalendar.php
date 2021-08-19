<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;

class PeriodicCalendar implements CalendarWithDateRange, CalendarWithOpeningHours
{
    private DateRange $dateRange;

    private OpeningHours $openingHours;

    private Status $status;

    private BookingAvailabilityType $bookingAvailability;

    public function __construct(
        DateRange $dateRange,
        OpeningHours $openingHours
    ) {
        $this->dateRange = $dateRange;
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
        return CalendarType::periodic();
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function getBookingAvailability(): BookingAvailabilityType
    {
        return $this->bookingAvailability;
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
