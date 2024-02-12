<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\Event;

use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Theme;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;

class MajorInfo
{
    private Title $title;

    private EventType $type;

    private LocationId $location;

    private Calendar $calendar;

    private ?Theme $theme;

    public function __construct(
        Title $title,
        EventType $type,
        LocationId $location,
        Calendar $calendar,
        Theme $theme = null
    ) {
        $this->title = $title;
        $this->type = $type;
        $this->location = $location;
        $this->calendar = $calendar;
        $this->theme = $theme;
    }

    public function getTitle(): Title
    {
        return $this->title;
    }

    public function getType(): EventType
    {
        return $this->type;
    }

    public function getLocation(): LocationId
    {
        return $this->location;
    }

    public function getCalendar(): Calendar
    {
        return $this->calendar;
    }

    public function getTheme(): ?Theme
    {
        return $this->theme;
    }
}
