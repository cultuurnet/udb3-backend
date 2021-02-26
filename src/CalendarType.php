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
    public const SINGLE = 'single';
    public const MULTIPLE = 'multiple';
    public const PERIODIC = 'periodic';
    public const PERMANENT = 'permanent';
}
