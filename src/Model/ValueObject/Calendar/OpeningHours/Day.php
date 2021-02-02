<?php

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
    /**
     * @inheritdoc
     */
    public static function getAllowedValues()
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
