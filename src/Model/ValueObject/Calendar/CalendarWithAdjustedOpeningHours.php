<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

interface CalendarWithAdjustedOpeningHours extends Calendar
{
    public function getAdjustedOpeningHours(): AdjustedOpeningHoursCollection;

    public function withAdjustedOpeningHours(AdjustedOpeningHoursCollection $adjustedOpeningHours): static;
}
