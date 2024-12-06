<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Commands;

use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Offer\Commands\AbstractCommand;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;

class UpdateMajorInfo extends AbstractCommand
{
    private Title $title;

    private Category $eventType;

    private ?Category $theme;

    private LocationId $location;

    private Calendar $calendar;

    public function __construct(
        string $eventId,
        Title $title,
        Category $eventType,
        LocationId $location,
        Calendar $calendar,
        Category $theme = null
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

    public function getEventType(): Category
    {
        return $this->eventType;
    }

    public function getTheme(): ?Category
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
