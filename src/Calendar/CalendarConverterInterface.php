<?php

namespace CultuurNet\UDB3\Calendar;

use CultuurNet\UDB3\CalendarInterface;

interface CalendarConverterInterface
{
    /**
     * @param CalendarInterface $calendar
     * @return \CultureFeed_Cdb_Data_Calendar $cdbCalendar
     */
    public function toCdbCalendar(CalendarInterface $calendar);
}
