<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

final class BookingAvailability
{
    private BookingAvailabilityType $type;

    private ?int $capacity = null;

    private ?int $availability = null;

    public function __construct(BookingAvailabilityType $type)
    {
        $this->type = $type;
    }

    public function getType(): BookingAvailabilityType
    {
        return $this->type;
    }

    public function getCapacity(): ?int
    {
        return $this->capacity;
    }

    public function getAvailability(): ?int
    {
        return $this->availability;
    }

    public function withCapacity(?int $capacity): self
    {
        $clone = clone $this;
        $clone->capacity = $capacity;
        return $clone;
    }

    public function withAvailability(?int $availability): self
    {
        $clone = clone $this;
        $clone->availability = $availability;
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
