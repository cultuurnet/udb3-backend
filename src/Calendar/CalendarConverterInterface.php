<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Calendar;

interface CalendarConverterInterface
{
    /**
     * @return \CultureFeed_Cdb_Data_Calendar $cdbCalendar
     */
    public function toCdbCalendar(CalendarInterface $calendar);
}
