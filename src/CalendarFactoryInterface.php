<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

use CultuurNet\UDB3\Calendar\Calendar;

interface CalendarFactoryInterface
{
    /**
     * @return Calendar
     */
    public function createFromCdbCalendar(
        \CultureFeed_Cdb_Data_Calendar $cdbCalendar
    );

    /**
     * @return Calendar
     */
    public function createFromWeekScheme(
        \CultureFeed_Cdb_Data_Calendar_Weekscheme $weekScheme = null
    );
}
