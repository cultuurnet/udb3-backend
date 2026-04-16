<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\ClosedDays;

interface CalendarWithClosedDays extends Calendar
{
    public function getClosedDays(): ClosedDays;

    public function withClosedDays(ClosedDays $closedDays): static;
}
