<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ValueObjects;

use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsString;
use InvalidArgumentException;

/**
 * @deprecated
 *   Use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailabilityType instead where possible.
 */
class BookingAvailabilityType
{
    use IsString;

    private const AVAILABLE = 'Available';
    private const UNAVAILABLE = 'Unavailable';

    /**
     * @var string[]
     */
    private const ALLOWED_VALUES = [
        self::AVAILABLE,
        self::UNAVAILABLE,
    ];

    private function __construct(string $value)
    {
        if (!\in_array($value, self::ALLOWED_VALUES, true)) {
            throw new InvalidArgumentException('Booking availability does not support the value "' . $value . '"');
        }
        $this->value = $value;
    }

    public static function available(): BookingAvailabilityType
    {
        return new BookingAvailabilityType(self::AVAILABLE);
    }

    public static function unavailable(): BookingAvailabilityType
    {
        return new BookingAvailabilityType(self::UNAVAILABLE);
    }

    public static function fromNative(string $value): BookingAvailabilityType
    {
        return new BookingAvailabilityType($value);
    }
}
