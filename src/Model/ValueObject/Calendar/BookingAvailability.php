<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

final class BookingAvailability
{
    private BookingAvailabilityType $type;

    private ?int $capacity = null;

    private ?int $remainingCapacity = null;

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

    public function getRemainingCapacity(): ?int
    {
        return $this->remainingCapacity;
    }

    public function withCapacity(?int $capacity): self
    {
        $clone = clone $this;
        $clone->capacity = $capacity;
        return $clone;
    }

    public function withRemainingCapacity(?int $remainingCapacity): self
    {
        $clone = clone $this;
        $clone->remainingCapacity = $remainingCapacity;
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
