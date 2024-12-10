<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\Calendar;

interface CalendarConverterInterface
{
    public function toCdbCalendar(Calendar $calendar): \CultureFeed_Cdb_Data_Calendar;
}
