<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

use CultuurNet\UDB3\Model\ValueObject\String\Enum;

/**
 * @deprecated
 *   Use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarType instead where possible.
 */
class CalendarType extends Enum
{
    public const SINGLE = 'single';
    public const MULTIPLE = 'multiple';
    public const PERIODIC = 'periodic';
    public const PERMANENT = 'permanent';

    public static function getAllowedValues(): array
    {
        return [
            self::SINGLE,
            self::MULTIPLE,
            self::PERIODIC,
            self::PERMANENT,
        ];
    }

    public static function SINGLE(): CalendarType
    {
        return new CalendarType(self::SINGLE);
    }

    public static function MULTIPLE(): CalendarType
    {
        return new CalendarType(self::MULTIPLE);
    }

    public static function PERIODIC(): CalendarType
    {
        return new CalendarType(self::PERIODIC);
    }

    public static function PERMANENT(): CalendarType
    {
        return new CalendarType(self::PERMANENT);
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
