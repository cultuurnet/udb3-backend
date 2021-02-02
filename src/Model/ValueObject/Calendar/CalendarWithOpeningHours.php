<?php

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;

interface CalendarWithOpeningHours extends Calendar
{
    public function getOpeningHours(): OpeningHours;
}
