<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Events;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Place\PlaceEvent;
use CultuurNet\UDB3\Theme;
use CultuurNet\UDB3\Title;

final class MajorInfoUpdated extends PlaceEvent
{
    /**
     * @var Title
     */
    private $title;

    /**
     * @var EventType
     */
    private $eventType;

    /**
     * @var Theme|null
     */
    private $theme;

    /**
     * @var Address
     */
    private $address;

    /**
     * @var Calendar
     */
    private $calendar;

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
