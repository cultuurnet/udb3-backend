<?php

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

interface CalendarWithSubEvents
{
    public function getSubEvents(): SubEvents;
}
