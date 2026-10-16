<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\TimeImmutableRange;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use DateTimeImmutable;

final class SubEventUpdate
{
    private int $subEventId;
    private ?DateTimeImmutable $startDate = null;
    private ?DateTimeImmutable $endDate = null;
    private ?Status $status = null;
    private ?BookingAvailability $bookingAvailability = null;
    private ?TimeImmutableRange $childcareTimeRange = null;

    private ?bool $hasOvernightStay = null;

    /**
     * The waiting list url follows the PATCH convention independently of the booking availability type:
     * not updated (left unchanged), updated with a url (set) or updated without a url (cleared).
     */
    private bool $updatesWaitingListUrl = false;
    private ?Url $waitingListUrl = null;

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

    public function getChildcareTimeRange(): ?TimeImmutableRange
    {
        return $this->childcareTimeRange;
    }

    /**
     * An empty range clears childcare instead of setting it, so it does not count as setting childcare.
     */
    public function setsChildcare(): bool
    {
        return $this->childcareTimeRange !== null && !$this->childcareTimeRange->isEmpty();
    }

    public function withChildcareTimeRange(?TimeImmutableRange $childcareTimeRange): self
    {
        $c = clone $this;
        $c->childcareTimeRange = $childcareTimeRange;
        return $c;
    }

    public function getHasOvernightStay(): ?bool
    {
        return $this->hasOvernightStay;
    }

    public function withHasOvernightStay(?bool $hasOvernightStay): self
    {
        $c = clone $this;
        $c->hasOvernightStay = $hasOvernightStay;
        return $c;
    }

    public function updatesWaitingListUrl(): bool
    {
        return $this->updatesWaitingListUrl;
    }

    public function getWaitingListUrl(): ?Url
    {
        return $this->waitingListUrl;
    }

    public function withWaitingListUrl(Url $waitingListUrl): self
    {
        $c = clone $this;
        $c->updatesWaitingListUrl = true;
        $c->waitingListUrl = $waitingListUrl;
        return $c;
    }

    public function withoutWaitingListUrl(): self
    {
        $c = clone $this;
        $c->updatesWaitingListUrl = true;
        $c->waitingListUrl = null;
        return $c;
    }
}
