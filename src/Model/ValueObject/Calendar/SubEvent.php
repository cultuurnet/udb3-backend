<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Contact\BookingInfo;

final class SubEvent
{
    private DateRange $dateRange;

    private Status $status;

    private BookingAvailability $bookingAvailability;

    private BookingInfo $bookingInfo;

    public function __construct(
        DateRange $dateRange,
        Status $status,
        BookingAvailability $bookingAvailability,
        BookingInfo $bookingInfo
    ) {
        $this->dateRange = $dateRange;
        $this->status = $status;
        $this->bookingAvailability = $bookingAvailability;
        $this->bookingInfo = $bookingInfo;
    }

    public static function createAvailable(DateRange $dateRange): self
    {
        return new self(
            $dateRange,
            new Status(StatusType::Available()),
            new BookingAvailability(BookingAvailabilityType::Available()),
            new BookingInfo()
        );
    }

    public function withStatus(Status $status): self
    {
        $clone = clone $this;
        $clone->status = $status;
        return $clone;
    }

    public function withBookingAvailability(BookingAvailability $bookingAvailability): self
    {
        $clone = clone $this;
        $clone->bookingAvailability = $bookingAvailability;
        return $clone;
    }

    public function withBookingInfo(BookingInfo $bookingInfo): self
    {
        $clone = clone $this;
        $clone->bookingInfo = $bookingInfo;
        return $clone;
    }

    public function getDateRange(): DateRange
    {
        return $this->dateRange;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function getBookingAvailability(): BookingAvailability
    {
        return $this->bookingAvailability;
    }

    public function getBookingInfo(): BookingInfo
    {
        return $this->bookingInfo;
    }
}
