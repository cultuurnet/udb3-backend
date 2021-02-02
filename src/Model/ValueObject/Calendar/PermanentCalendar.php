<?php

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;

class PermanentCalendar implements CalendarWithOpeningHours
{
    /**
     * @var OpeningHours
     */
    private $openingHours;

    /**
     * @var Status
     */
    private $status;

    public function __construct(OpeningHours $openingHours)
    {
        $this->openingHours = $openingHours;
        $this->status = new Status(StatusType::Available());
    }

    public function withStatus(Status $status): Calendar
    {
        $clone = clone $this;
        $clone->status = $status;
        return $clone;
    }

    public function getType(): CalendarType
    {
        return CalendarType::permanent();
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function getOpeningHours(): OpeningHours
    {
        return $this->openingHours;
    }
}
