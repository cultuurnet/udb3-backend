<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\Calendar;

interface CalendarFactoryInterface
{
    public function createFromCdbCalendar(\CultureFeed_Cdb_Data_Calendar $cdbCalendar): Calendar;

    public function createFromWeekScheme(\CultureFeed_Cdb_Data_Calendar_Weekscheme $weekScheme = null): Calendar;
}
