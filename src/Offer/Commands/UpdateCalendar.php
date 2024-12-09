<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Model\ValueObject\Calendar\Calendar;

final class UpdateCalendar extends AbstractCommand
{
    private Calendar $calendar;

    public function __construct(string $itemId, Calendar $calendar)
    {
        parent::__construct($itemId);

        $this->calendar = $calendar;
    }

    public function getCalendar(): Calendar
    {
        return $this->calendar;
    }
}
