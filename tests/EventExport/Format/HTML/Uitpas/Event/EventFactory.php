<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\Event;

use CultureFeed_Uitpas_Event_CultureEvent as Event;

class EventFactory
{
    /**
     * @param float|int $points
     */
    public function buildEventWithPoints($points): Event
    {
        $event = new Event();
        $event->cardSystems = [];
        $event->numberOfPoints = $points;
        return $event;
    }
}
