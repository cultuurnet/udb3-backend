<?php

namespace CultuurNet\UDB3;

interface CalendarFactoryInterface
{
    /**
     * @param \CultureFeed_Cdb_Data_Calendar $cdbCalendar
     * @return Calendar
     */
    public function createFromCdbCalendar(
        \CultureFeed_Cdb_Data_Calendar $cdbCalendar
    );

    /**
     * @param \CultureFeed_Cdb_Data_Calendar_Weekscheme|null $weekScheme
     * @return Calendar
     */
    public function createFromWeekScheme(
        \CultureFeed_Cdb_Data_Calendar_Weekscheme $weekScheme = null
    );
}
