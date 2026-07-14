<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\TimeImmutableRange;

final class SubEvent
{
    private DateRange $dateRange;

    private Status $status;

    private BookingAvailability $bookingAvailability;

    private ?TimeImmutableRange $childcareTimeRange = null;

    private bool $overnight = false;

    public function __construct(
        DateRange $dateRange,
        Status $status,
        BookingAvailability $bookingAvailability
    ) {
        $this->dateRange = $dateRange;
        $this->status = $status;
        $this->bookingAvailability = $bookingAvailability;
    }

    public static function createAvailable(DateRange $dateRange): self
    {
        return new self(
            $dateRange,
            new Status(StatusType::Available()),
            new BookingAvailability(BookingAvailabilityType::Available())
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

    public function withChildcareTimeRange(?TimeImmutableRange $childcareTimeRange): self
    {
        $clone = clone $this;
        $clone->childcareTimeRange = $childcareTimeRange;
        return $clone;
    }

    public function withOvernight(bool $overnight): self
    {
        $clone = clone $this;
        $clone->overnight = $overnight;
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

    public function getChildcareTimeRange(): ?TimeImmutableRange
    {
        return $this->childcareTimeRange;
    }

    public function isOvernight(): bool
    {
        return $this->overnight;
    }
}
