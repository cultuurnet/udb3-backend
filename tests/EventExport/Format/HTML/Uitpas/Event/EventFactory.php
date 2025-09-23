<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\Event;

use CultureFeed_Uitpas_Event_CultureEvent as Event;

class EventFactory
{
    public function buildEventWithPoints(int $points): Event
    {
        $event = new Event();
        $event->cardSystems = [];
        $event->numberOfPoints = $points;
        return $event;
    }
}
