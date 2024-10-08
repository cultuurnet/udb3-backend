<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Events;

use CultuurNet\UDB3\Calendar\Calendar;

abstract class AbstractCalendarUpdated extends AbstractEvent
{
    private Calendar $calendar;

    final public function __construct(string $itemId, Calendar $calendar)
    {
        parent::__construct($itemId);

        $this->calendar = $calendar;
    }

    public function getCalendar(): Calendar
    {
        return $this->calendar;
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'calendar' => $this->calendar->serialize(),
        ];
    }

    public static function deserialize(array $data): AbstractCalendarUpdated
    {
        return new static(
            $data['item_id'],
            Calendar::deserialize($data['calendar'])
        );
    }
}
