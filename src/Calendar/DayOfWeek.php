<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Day;
use CultuurNet\UDB3\Model\ValueObject\String\Enum;

/**
 * @deprecated
 *   Use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Day instead where possible.
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

    public static function getAllowedValues(): array
    {
        return [
            self::MONDAY,
            self::TUESDAY,
            self::WEDNESDAY,
            self::THURSDAY,
            self::FRIDAY,
            self::SATURDAY,
            self::SUNDAY,
        ];
    }

    public static function MONDAY(): DayOfWeek
    {
        return new DayOfWeek(self::MONDAY);
    }

    public static function TUESDAY(): DayOfWeek
    {
        return new DayOfWeek(self::TUESDAY);
    }

    public static function WEDNESDAY(): DayOfWeek
    {
        return new DayOfWeek(self::WEDNESDAY);
    }

    public static function THURSDAY(): DayOfWeek
    {
        return new DayOfWeek(self::THURSDAY);
    }

    public static function FRIDAY(): DayOfWeek
    {
        return new DayOfWeek(self::FRIDAY);
    }

    public static function SATURDAY(): DayOfWeek
    {
        return new DayOfWeek(self::SATURDAY);
    }

    public static function SUNDAY(): DayOfWeek
    {
        return new DayOfWeek(self::SUNDAY);
    }

    public static function fromUdb3ModelDay(Day $day): DayOfWeek
    {
        return new DayOfWeek($day->toString());
    }
}
