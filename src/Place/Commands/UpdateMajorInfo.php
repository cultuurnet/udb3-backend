<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Commands;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Calendar;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Offer\Commands\AbstractCommand;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;

class UpdateMajorInfo extends AbstractCommand
{
    private Title $title;

    private Category $eventType;

    private Address $address;

    private Calendar $calendar;

    public function __construct(
        string $placeId,
        Title $title,
        Category $eventType,
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

    public function getEventType(): Category
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
