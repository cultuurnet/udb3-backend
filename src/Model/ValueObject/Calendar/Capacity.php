<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use InvalidArgumentException;

final class Capacity
{
    private ?CapacityType $type;

    private ?int $maximum;

    private ?int $taken;

    private function __construct(?CapacityType $type, ?int $maximum, ?int $taken)
    {
        if ($maximum !== null && $maximum < 1) {
            throw new InvalidArgumentException('The maximum capacity should be at least 1.');
        }

        // Taken can be higher than the maximum, because overbooking happens.
        if ($taken !== null && $taken < 0) {
            throw new InvalidArgumentException('The taken capacity should not be negative.');
        }

        $this->type = $type;
        $this->maximum = $maximum;
        $this->taken = $taken;
    }

    public static function limited(int $maximum, ?int $taken = null): self
    {
        return new self(CapacityType::Limited, $maximum, $taken);
    }

    public static function unlimited(?int $taken = null): self
    {
        return new self(CapacityType::Unlimited, null, $taken);
    }

    public static function unknown(): self
    {
        return new self(null, null, null);
    }

    public function isUnknown(): bool
    {
        return $this->type === null;
    }

    public function getType(): ?CapacityType
    {
        return $this->type;
    }

    public function getMaximum(): ?int
    {
        return $this->maximum;
    }

    public function getTaken(): ?int
    {
        return $this->taken;
    }
}
