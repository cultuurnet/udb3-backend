<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

use ValueObjects\Enum\Enum;

/**
 * @deprecated
 *   Use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarType instead where possible.
 *
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
