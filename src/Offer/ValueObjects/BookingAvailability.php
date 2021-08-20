<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ValueObjects;

use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability as Udb3ModelBookingAvailability;

final class BookingAvailability implements Serializable
{
    private BookingAvailabilityType $type;

    public function __construct(BookingAvailabilityType $type)
    {
        $this->type = $type;
    }

    public static function available(): BookingAvailability
    {
        return new BookingAvailability(BookingAvailabilityType::available());
    }

    public static function unavailable(): BookingAvailability
    {
        return new BookingAvailability(BookingAvailabilityType::unavailable());
    }

    public function getType(): BookingAvailabilityType
    {
        return $this->type;
    }

    public function equals(BookingAvailability $bookingAvailability): bool
    {
        return $this->type->toNative() === $bookingAvailability->getType()->toNative();
    }

    public static function deserialize(array $data): BookingAvailability
    {
        return new BookingAvailability(BookingAvailabilityType::fromNative($data['type']));
    }

    public function serialize(): array
    {
        return [
            'type' => $this->type->toNative(),
        ];
    }

    public static function fromUdb3ModelBookingAvailability(
        Udb3ModelBookingAvailability $udb3ModelBookingAvailability
    ): self {
        return new BookingAvailability(BookingAvailabilityType::fromNative(
            $udb3ModelBookingAvailability->getType()->toString()
        ));
    }
}
