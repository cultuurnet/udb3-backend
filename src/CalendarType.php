<?php

namespace CultuurNet\UDB3;

use ValueObjects\Enum\Enum;

/**
 * @method static CalendarType SINGLE()
 * @method static CalendarType MULTIPLE()
 * @method static CalendarType PERIODIC()
 * @method static CalendarType PERMANENT()
 */
class CalendarType extends Enum
{
    const SINGLE = 'single';
    const MULTIPLE = 'multiple';
    const PERIODIC = 'periodic';
    const PERMANENT = 'permanent';
}
