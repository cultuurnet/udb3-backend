<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\String\Enum;

/**
 * @method static CalendarType single()
 * @method static CalendarType multiple()
 * @method static CalendarType periodic()
 * @method static CalendarType permanent()
 */
class CalendarType extends Enum
{
    /**
     * @inheritdoc
     */
    public static function getAllowedValues(): array
    {
        return [
            'single',
            'multiple',
            'periodic',
            'permanent',
        ];
    }
}
