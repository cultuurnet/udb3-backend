<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHoursAdjustedPeriods;

interface CalendarWithOpeningHoursAdjusted extends Calendar
{
    public function getOpeningHoursAdjusted(): OpeningHoursAdjustedPeriods;

    public function withOpeningHoursAdjusted(OpeningHoursAdjustedPeriods $openingHoursAdjustedPeriods): static;
}
