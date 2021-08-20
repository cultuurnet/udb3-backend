<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\String\Enum;

/**
 * @method static BookingAvailabilityType Available()
 * @method static BookingAvailabilityType Unavailable()
 */
final class BookingAvailabilityType extends Enum
{
    public static function getAllowedValues(): array
    {
        return [
            'Available',
            'Unavailable',
        ];
    }
}
