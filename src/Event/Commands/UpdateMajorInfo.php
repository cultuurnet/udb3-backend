<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Commands;

use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Offer\Commands\AbstractCommand;
use CultuurNet\UDB3\Theme;
use CultuurNet\UDB3\Title as LegacyTitle;

/**
 * Provides a command to update the major info of the event.
 */
class UpdateMajorInfo extends AbstractCommand
{
    private Title $title;

    /**
     * @var EventType
     */
    private $eventType;

    /**
     * @var Theme|null
     */
    private $theme;

    /**
     * @var LocationId
     */
    private $location;

    /**
     * @var Calendar
     */
    private $calendar;

    public function __construct(
        string $eventId,
        Title $title,
        EventType $eventType,
        LocationId $location,
        Calendar $calendar,
        Theme $theme = null
    ) {
        parent::__construct($eventId);
        $this->title = $title;
        $this->eventType = $eventType;
        $this->location = $location;
        $this->calendar = $calendar;
        $this->theme = $theme;
    }

    /**
     * @return LegacyTitle
     */
    public function getTitle()
    {
        return LegacyTitle::fromUdb3ModelTitle($this->title);
    }

    /**
     * @return EventType
     */
    public function getEventType()
    {
        return $this->eventType;
    }

    /**
     * @return Theme|null
     */
    public function getTheme()
    {
        return $this->theme;
    }

    /**
     * @return LocationId
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @return Calendar
     */
    public function getCalendar()
    {
        return $this->calendar;
    }
}
