<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Holidays;

enum HolidayType: string
{
    case PublicHolidays = 'PublicHolidays';
    case SchoolHolidays = 'SchoolHolidays';

    public function outputType(): string
    {
        return match ($this) {
            self::PublicHolidays => 'holidays',
            self::SchoolHolidays => 'schoolHolidays',
        };
    }
}
