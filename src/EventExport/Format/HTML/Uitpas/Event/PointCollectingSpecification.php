<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\Event;

class PointCollectingSpecification implements EventSpecification
{
    public function isSatisfiedBy(\CultureFeed_Uitpas_Event_CultureEvent $event): bool
    {
        return $event->numberOfPoints !== null && $event->numberOfPoints > 0;
    }
}
