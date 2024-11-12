<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ValueObjects;

use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability as Udb3ModelBookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailabilityType;
use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsString;

/**
 * @deprecated
 *   Use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability as much as possible, and convert to this using
 *   fromUdb3ModelBookingAvailability() where still needed.
 */
final class BookingAvailability implements Serializable
{
    use IsString;

    /**
     * Store the BookingAvailabilityType as a string to prevent serialization issues when the Calendar is part of a
     * command that gets queued in Redis, as the base Enum class that it extends from does not support serialization for
     * some reason.
     */
    public function __construct(BookingAvailabilityType $type)
    {
        $this->value = $type->toString();
    }

    public static function available(): BookingAvailability
    {
        return new BookingAvailability(BookingAvailabilityType::Available());
    }

    public static function unavailable(): BookingAvailability
    {
        return new BookingAvailability(BookingAvailabilityType::Unavailable());
    }

    public function getType(): BookingAvailabilityType
    {
        return new BookingAvailabilityType($this->value);
    }

    public function equals(BookingAvailability $bookingAvailability): bool
    {
        return $this->value === $bookingAvailability->getType()->toString();
    }

    public static function deserialize(array $data): BookingAvailability
    {
        return new BookingAvailability(new BookingAvailabilityType($data['type']));
    }

    public function serialize(): array
    {
        return [
            'type' => $this->value,
        ];
    }

    public static function fromUdb3ModelBookingAvailability(
        Udb3ModelBookingAvailability $udb3ModelBookingAvailability
    ): self {
        return new BookingAvailability($udb3ModelBookingAvailability->getType());
    }

    public function toUdb3ModelBookingAvailability(): Udb3ModelBookingAvailability
    {
        return new Udb3ModelBookingAvailability($this->getType());
    }
}
