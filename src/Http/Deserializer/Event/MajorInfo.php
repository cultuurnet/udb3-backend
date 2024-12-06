<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\Event;

use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;

class MajorInfo
{
    private Title $title;

    private Category $type;

    private LocationId $location;

    private Calendar $calendar;

    private ?Category $theme;

    public function __construct(
        Title $title,
        Category $type,
        LocationId $location,
        Calendar $calendar,
        Category $theme = null
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

    public function getType(): Category
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

    public function getTheme(): ?Category
    {
        return $this->theme;
    }
}
