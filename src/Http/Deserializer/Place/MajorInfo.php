<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\Place;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Title;

class MajorInfo
{
    /**
     * @var Title
     */
    private $title;

    /**
     * @var EventType
     */
    private $type;

    /**
     * @var Address
     */
    private $address;

    /**
     * @var Calendar
     */
    private $calendar;

    public function __construct(
        Title $title,
        EventType $type,
        Address $address,
        Calendar $calendar
    ) {
        $this->title = $title;
        $this->type = $type;
        $this->address = $address;
        $this->calendar = $calendar;
    }

    /**
     * @return Title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return EventType
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return Address
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @return Calendar
     */
    public function getCalendar()
    {
        return $this->calendar;
    }
}
