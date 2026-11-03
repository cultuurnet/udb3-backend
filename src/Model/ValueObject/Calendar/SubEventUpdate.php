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
    private ?BookingAvailabilityType $bookingAvailabilityType = null;
    private ?SubEventBookingInfo $bookingInfo = null;
    private ?TimeImmutableRange $childcareTimeRange = null;

    private ?bool $hasOvernightStay = null;

    /**
     * The waiting list url is updated independently of the booking availability type, as a PATCH can leave it
     * unchanged, set it or clear it, which a BookingAvailability with or without a url can't express.
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

    public function withBookingAvailabilityType(?BookingAvailabilityType $bookingAvailabilityType): self
    {
        $c = clone $this;
        $c->bookingAvailabilityType = $bookingAvailabilityType;
        return $c;
    }

    public function getBookingInfo(): ?SubEventBookingInfo
    {
        return $this->bookingInfo;
    }

    public function withBookingInfo(?SubEventBookingInfo $bookingInfo): self
    {
        $c = clone $this;
        $c->bookingInfo = $bookingInfo;
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

    /**
     * The type and the waiting list url are patched independently, so updating only the type keeps the
     * waiting list url of the sub event, and the waiting list url only changes when the update mentions it.
     */
    public function applyToBookingAvailability(BookingAvailability $bookingAvailability): BookingAvailability
    {
        if ($this->bookingAvailabilityType !== null) {
            $bookingAvailability = $bookingAvailability->withType($this->bookingAvailabilityType);
        }

        if ($this->updatesWaitingListUrl) {
            $bookingAvailability = $bookingAvailability->withWaitingListUrl($this->waitingListUrl);
        }

        return $bookingAvailability;
    }
}
