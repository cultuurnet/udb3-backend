<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Commands;

use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Offer\Commands\AbstractCommand;
use CultuurNet\UDB3\Theme;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;

/**
 * Provides a command to update the major info of the event.
 */
class UpdateMajorInfo extends AbstractCommand
{
    private Title $title;

    private EventType $eventType;

    private ?Theme $theme;

    private LocationId $location;

    private Calendar $calendar;

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

    public function getTitle(): Title
    {
        return $this->title;
    }

    public function getEventType(): EventType
    {
        return $this->eventType;
    }

    public function getTheme(): ?Theme
    {
        return $this->theme;
    }

    public function getLocation(): LocationId
    {
        return $this->location;
    }

    public function getCalendar(): Calendar
    {
        return $this->calendar;
    }
}
