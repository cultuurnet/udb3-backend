<?php

namespace CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\Event;

use \CultureFeed_Uitpas_Event_CultureEvent as Event;

class EventFactory
{
    /**
     * @param float|int $points
     * @return Event
     */
    public function buildEventWithPoints($points)
    {
        $event = new Event();
        $event->cardSystems = [];
        $event->numberOfPoints = $points;
        return $event;
    }
}
