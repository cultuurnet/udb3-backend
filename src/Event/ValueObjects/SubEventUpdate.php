<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ValueObjects;

use CultuurNet\UDB3\Offer\ValueObjects\BookingAvailability;

final class SubEventUpdate
{
    private int $subEventIndex;

    private ?Status $status = null;

    private ?BookingAvailability $bookingAvailability = null;

    public function __construct(int $subEventIndex)
    {
        $this->subEventIndex = $subEventIndex;
    }

    public function withStatus(Status $status): SubEventUpdate
    {
        $clone = clone $this;
        $clone->status = $status;
        return $clone;
    }

    public function withBookingAvailability(BookingAvailability $bookingAvailability): SubEventUpdate
    {
        $clone = clone $this;
        $clone->bookingAvailability = $bookingAvailability;
        return $clone;
    }

    public function getSubEventIndex(): int
    {
        return $this->subEventIndex;
    }

    public function getStatus(): ?Status
    {
        return $this->status;
    }

    public function getBookingAvailability(): ?BookingAvailability
    {
        return $this->bookingAvailability;
    }
}
