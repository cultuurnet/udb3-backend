<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\EventSourcing\AggregateCopiedEventInterface;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar\CalendarSerializer;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Calendar;
use CultuurNet\UDB3\Offer\Events\AbstractEvent;

final class EventCopied extends AbstractEvent implements AggregateCopiedEventInterface
{
    private string $originalEventId;

    private Calendar $calendar;

    public function __construct(
        string $eventId,
        string $originalEventId,
        Calendar $calendar
    ) {
        parent::__construct($eventId);

        if (!is_string($originalEventId)) {
            throw new \InvalidArgumentException(
                'Expected originalEventId to be a string, received ' . gettype($originalEventId)
            );
        }

        $this->originalEventId = $originalEventId;
        $this->calendar = $calendar;
    }

    public function getParentAggregateId(): string
    {
        return $this->originalEventId;
    }

    public function getOriginalEventId(): string
    {
        return $this->originalEventId;
    }


    public function getCalendar(): Calendar
    {
        return $this->calendar;
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'original_event_id' => $this->getOriginalEventId(),
            'calendar' => (new CalendarSerializer($this->calendar))->serialize(),
        ];
    }

    public static function deserialize(array $data): EventCopied
    {
        return new self(
            $data['item_id'],
            $data['original_event_id'],
            CalendarSerializer::deserialize($data['calendar'])
        );
    }
}
