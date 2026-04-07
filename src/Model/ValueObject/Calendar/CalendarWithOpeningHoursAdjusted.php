<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

interface CalendarWithOpeningHoursAdjusted extends Calendar
{
    public function getOpeningHoursAdjusted(): OpeningHoursAdjustedPeriods;

    public function withOpeningHoursAdjusted(OpeningHoursAdjustedPeriods $openingHoursAdjustedPeriods): static;
}
