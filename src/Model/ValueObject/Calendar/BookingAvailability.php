<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\String\Enum;

/**
 * @method static BookingAvailability Available()
 * @method static BookingAvailability Unavailable()
 */
final class BookingAvailability extends Enum
{
    public static function getAllowedValues(): array
    {
        return [
            'Available',
            'Unavailable',
        ];
    }
}
