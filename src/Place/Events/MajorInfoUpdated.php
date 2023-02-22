<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Events;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\EventSourcing\ConvertsToGranularEvents;
use CultuurNet\UDB3\Place\PlaceEvent;
use CultuurNet\UDB3\Title;

final class MajorInfoUpdated extends PlaceEvent implements ConvertsToGranularEvents
{
    private Title $title;
    private EventType $eventType;
    private Address $address;
    private Calendar $calendar;

    final public function __construct(
        string $placeId,
        Title $title,
        EventType $eventType,
        Address $address,
        Calendar $calendar
    ) {
        parent::__construct($placeId);

        $this->title = $title;
        $this->eventType = $eventType;
        $this->address = $address;
        $this->calendar = $calendar;
    }

    public function getTitle(): Title
    {
        return $this->title;
    }

    public function getEventType(): EventType
    {
        return $this->eventType;
    }

    public function getCalendar(): Calendar
    {
        return $this->calendar;
    }

    public function getAddress(): Address
    {
        return $this->address;
    }

    public function toGranularEvents(): array
    {
        return array_values(
            array_filter(
                [
                    new TitleUpdated($this->placeId, $this->title),
                    new TypeUpdated($this->placeId, $this->eventType),
                    new AddressUpdated($this->placeId, $this->address),
                    new CalendarUpdated($this->placeId, $this->calendar),
                ]
            )
        );
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'title' => (string)$this->getTitle(),
            'event_type' => $this->getEventType()->serialize(),
            'address' => $this->getAddress()->serialize(),
            'calendar' => $this->getCalendar()->serialize(),
        ];
    }

    public static function deserialize(array $data): MajorInfoUpdated
    {
        return new static(
            $data['place_id'],
            new Title($data['title']),
            EventType::deserialize($data['event_type']),
            Address::deserialize($data['address']),
            Calendar::deserialize($data['calendar'])
        );
    }
}
