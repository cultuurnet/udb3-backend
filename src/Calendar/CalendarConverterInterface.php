<?php

namespace CultuurNet\UDB3\Calendar;

use CultuurNet\UDB3\CalendarInterface;

interface CalendarConverterInterface
{
    /**
     * @return \CultureFeed_Cdb_Data_Calendar $cdbCalendar
     */
    public function toCdbCalendar(CalendarInterface $calendar);
}
