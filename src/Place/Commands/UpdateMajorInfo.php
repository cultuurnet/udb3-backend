<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Commands;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Offer\Commands\AbstractCommand;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;

class UpdateMajorInfo extends AbstractCommand
{
    private Title $title;

    private EventType $eventType;

    private Address $address;

    private Calendar $calendar;

    public function __construct(
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

    public function getAddress(): Address
    {
        return $this->address;
    }

    public function getCalendar(): Calendar
    {
        return $this->calendar;
    }
}
