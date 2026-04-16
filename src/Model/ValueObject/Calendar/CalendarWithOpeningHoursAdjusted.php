<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHoursAdjustedDays;

interface CalendarWithOpeningHoursAdjusted extends Calendar
{
    public function getOpeningHoursAdjustedPeriods(): OpeningHoursAdjustedDays;

    public function withOpeningHoursAdjustedPeriods(OpeningHoursAdjustedDays $openingHoursAdjustedPeriods): static;
}
