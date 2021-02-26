<?php

namespace CultuurNet\UDB3\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Day;
use ValueObjects\Enum\Enum;

/**
 * @method static DayOfWeek MONDAY()
 * @method static DayOfWeek TUESDAY()
 * @method static DayOfWeek WEDNESDAY()
 * @method static DayOfWeek THURSDAY()
 * @method static DayOfWeek FRIDAY()
 * @method static DayOfWeek SATURDAY()
 * @method static DayOfWeek SUNDAY()
 *
 * @todo Replace by CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Day.
 */
final class DayOfWeek extends Enum
{
    public const MONDAY = 'monday';
    public const TUESDAY = 'tuesday';
    public const WEDNESDAY = 'wednesday';
    public const THURSDAY = 'thursday';
    public const FRIDAY = 'friday';
    public const SATURDAY = 'saturday';
    public const SUNDAY = 'sunday';

    public static function fromUdb3ModelDay(Day $day): DayOfWeek
    {
        return self::fromNative($day->toString());
    }
}
