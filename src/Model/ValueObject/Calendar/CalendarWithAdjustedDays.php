<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\AdjustedDays;

interface CalendarWithAdjustedDays extends Calendar
{
    public function getAdjustedDays(): AdjustedDays;

    public function withAdjustedDays(AdjustedDays $adjustedDays): static;
}
