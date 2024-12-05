<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\Place;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;

class MajorInfo
{
    private Title $title;

    private Category $type;

    private Address $address;

    private Calendar $calendar;

    public function __construct(
        Title $title,
        Category $type,
        Address $address,
        Calendar $calendar
    ) {
        $this->title = $title;
        $this->type = $type;
        $this->address = $address;
        $this->calendar = $calendar;
    }

    public function getTitle(): Title
    {
        return $this->title;
    }

    public function getType(): Category
    {
        return $this->type;
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
