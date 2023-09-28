<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Calendar;

interface CalendarConverterInterface
{
    public function toCdbCalendar(CalendarInterface $calendar): \CultureFeed_Cdb_Data_Calendar;
}
