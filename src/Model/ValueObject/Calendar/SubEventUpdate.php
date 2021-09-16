<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use DateTimeImmutable;

final class SubEventUpdate
{
    private int $subEventId;
    private ?DateTimeImmutable $startDate = null;
    private ?DateTimeImmutable $endDate = null;
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

    public function getStartDate(): ?DateTimeImmutable
    {
        return $this->startDate;
    }

    public function withStartDate(?DateTimeImmutable $startDate): self
    {
        $c = clone $this;
        $c->startDate = $startDate;
        return $c;
    }

    public function getEndDate(): ?DateTimeImmutable
    {
        return $this->endDate;
    }

    public function withEndDate(?DateTimeImmutable $endDate): self
    {
        $c = clone $this;
        $c->endDate = $endDate;
        return $c;
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
