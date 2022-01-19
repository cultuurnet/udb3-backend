<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Commands;

use CultuurNet\UDB3\Model\ValueObject\Calendar\Calendar;

final class CopyEvent
{
    private string $originalEventId;
    private string $newEventId;
    private Calendar $calendar;

    public function __construct(string $originalEventId, string $newEventId, Calendar $calendar)
    {
        $this->originalEventId = $originalEventId;
        $this->newEventId = $newEventId;
        $this->calendar = $calendar;
    }

    public function getOriginalEventId(): string
    {
        return $this->originalEventId;
    }

    public function getNewEventId(): string
    {
        return $this->newEventId;
    }

    public function getCalendar(): Calendar
    {
        return $this->calendar;
    }
}
