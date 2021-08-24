<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

final class SubEventUpdate
{
    private int $subEventId;
    private ?Status $status = null;
    private ?BookingAvailability $bookingAvailability = null;

    public function __construct(int $subEventId)
    {
        $this->subEventId = $subEventId;
    }

    public function getSubEventId(): int
    {
        return $this->subEventId;
    }

    public function getStatus(): ?Status
    {
        return $this->status;
    }

    public function withStatus(?Status $status): self
    {
        $c = clone $this;
        $c->status = $status;
        return $c;
    }

    public function getBookingAvailability(): ?BookingAvailability
    {
        return $this->bookingAvailability;
    }

    public function withBookingAvailability(?BookingAvailability $bookingAvailability): self
    {
        $c = clone $this;
        $c->bookingAvailability = $bookingAvailability;
        return $c;
    }
}
