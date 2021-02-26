<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Calendar;

abstract class AbstractUpdateCalendar extends AbstractCommand
{
    /**
     * @var Calendar
     */
    private $calendar;

    /**
     * @param string $itemId
     */
    public function __construct($itemId, Calendar $calendar)
    {
        parent::__construct($itemId);

        $this->calendar = $calendar;
    }

    /**
     * @return Calendar
     */
    public function getCalendar()
    {
        return $this->calendar;
    }
}
