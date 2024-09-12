<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours;

use CultuurNet\UDB3\Model\ValueObject\String\Enum;

/**
 * @method static Day monday()
 * @method static Day tuesday()
 * @method static Day wednesday()
 * @method static Day thursday()
 * @method static Day friday()
 * @method static Day saturday()
 * @method static Day sunday()
 */
class Day extends Enum
{
    public static function getAllowedValues(): array
    {
        return [
            'monday',
            'tuesday',
            'wednesday',
            'thursday',
            'friday',
            'saturday',
            'sunday',
        ];
    }
}
