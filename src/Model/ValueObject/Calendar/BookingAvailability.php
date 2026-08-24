<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

final class BookingAvailability
{
    private BookingAvailabilityType $type;

    public function __construct(BookingAvailabilityType $type)
    {
        $this->type = $type;
    }

    public function getType(): BookingAvailabilityType
    {
        return $this->type;
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
