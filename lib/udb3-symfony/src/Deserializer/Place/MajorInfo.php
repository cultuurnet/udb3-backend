<?php

namespace CultuurNet\UDB3\Symfony\Deserializer\Place;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Theme;
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

    /**
     * @var Theme|null
     */
    private $theme;

    /**
     * @param Title $title
     * @param EventType $type
     * @param Address $address
     * @param Calendar $calendar
     * @param Theme|null $theme
     */
    public function __construct(
        Title $title,
        EventType $type,
        Address $address,
        Calendar $calendar,
        Theme $theme = null
    ) {
        $this->title = $title;
        $this->type = $type;
        $this->address = $address;
        $this->calendar = $calendar;
        $this->theme = $theme;
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

    /**
     * @return Theme|null
     */
    public function getTheme()
    {
        return $this->theme;
    }
}
