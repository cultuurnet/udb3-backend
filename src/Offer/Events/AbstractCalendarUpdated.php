<?php

namespace CultuurNet\UDB3\Offer\Events;

use CultuurNet\UDB3\Calendar;

abstract class AbstractCalendarUpdated extends AbstractEvent
{
    /**
     * @var Calendar
     */
    private $calendar;

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
