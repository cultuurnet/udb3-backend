<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Web\Url;

final class BookingAvailability
{
    private BookingAvailabilityType $type;

    /**
     * Only supported on the booking availability of a sub event, never on the top level of a calendar.
     */
    private ?Url $waitingListUrl = null;

    public function __construct(BookingAvailabilityType $type)
    {
        $this->type = $type;
    }

    public function getType(): BookingAvailabilityType
    {
        return $this->type;
    }

    public function withType(BookingAvailabilityType $type): self
    {
        $clone = clone $this;
        $clone->type = $type;
        return $clone;
    }

    public function getWaitingListUrl(): ?Url
    {
        return $this->waitingListUrl;
    }

    public function withWaitingListUrl(?Url $waitingListUrl): self
    {
        $clone = clone $this;
        $clone->waitingListUrl = $waitingListUrl;
        return $clone;
    }

    public static function Available(): self
    {
        return new self(BookingAvailabilityType::Available());
    }

    public static function Unavailable(): self
    {
        return new self(BookingAvailabilityType::Unavailable());
    }
}
