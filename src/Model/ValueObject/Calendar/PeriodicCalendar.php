<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHoursAdjustedPeriods;

final class PeriodicCalendar implements CalendarWithDateRange, CalendarWithOpeningHours, CalendarWithClosedDays, CalendarWithOpeningHoursAdjusted
{
    private DateRange $dateRange;

    private OpeningHours $openingHours;

    private ClosedDays $closedDays;

    private OpeningHoursAdjustedPeriods $openingHoursAdjustedPeriods;

    private Status $status;

    private BookingAvailability $bookingAvailability;

    public function __construct(
        DateRange $dateRange,
        OpeningHours $openingHours
    ) {
        $this->dateRange = $dateRange;
        $this->openingHours = $openingHours;
        $this->closedDays = new ClosedDays();
        $this->openingHoursAdjustedPeriods = new OpeningHoursAdjustedPeriods();
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
        return CalendarType::periodic();
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

    public function getOpeningHoursAdjusted(): OpeningHoursAdjustedPeriods
    {
        return $this->openingHoursAdjustedPeriods;
    }

    public function withOpeningHoursAdjusted(OpeningHoursAdjustedPeriods $openingHoursAdjustedPeriods): static
    {
        $clone = clone $this;
        $clone->openingHoursAdjustedPeriods = $openingHoursAdjustedPeriods;
        return $clone;
    }
}
