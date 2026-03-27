<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

interface CalendarWithClosedDays extends Calendar
{
    public function getClosedDays(): ClosedDays;

    public function withClosedDays(ClosedDays $closedDays): static;
}
