<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\String\Enum;

/**
 * @method static StatusType Available()
 * @method static StatusType TemporarilyUnavailable()
 * @method static StatusType Unavailable()
 */
class StatusType extends Enum
{
    public static function getAllowedValues(): array
    {
        return [
            'Available',
            'TemporarilyUnavailable',
            'Unavailable',
        ];
    }
}
