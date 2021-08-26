<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ValueObjects;

use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability as Udb3ModelBookingAvailability;

/**
 * @deprecated
 *   Use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability as much as possible, and convert to this using
 *   fromUdb3ModelBookingAvailability() where still needed.
 */
final class BookingAvailability implements Serializable
{
    /**
     * Store the BookingAvailabilityType as a string to prevent serialization issues when the Calendar is part of a
     * command that gets queued in Redis, as the base Enum class that it extends from does not support serialization for
     * some reason.
     */
    private string $type;

    public function __construct(BookingAvailabilityType $type)
    {
        $this->type = $type->toNative();
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
        return BookingAvailabilityType::fromNative($this->type);
    }

    public function equals(BookingAvailability $bookingAvailability): bool
    {
        return $this->type === $bookingAvailability->getType()->toNative();
    }

    public static function deserialize(array $data): BookingAvailability
    {
        return new BookingAvailability(BookingAvailabilityType::fromNative($data['type']));
    }

    public function serialize(): array
    {
        return [
            'type' => $this->type,
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
