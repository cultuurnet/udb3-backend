<?php

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

interface CalendarWithDateRange extends Calendar
{
    public function getStartDate(): \DateTimeImmutable;

    public function getEndDate(): \DateTimeImmutable;
}
