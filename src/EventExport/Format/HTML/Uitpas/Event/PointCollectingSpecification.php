<?php

namespace CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\Event;

class PointCollectingSpecification implements EventSpecification
{
    /**
     * {@inheritdoc}
     */
    public function isSatisfiedBy(\CultureFeed_Uitpas_Event_CultureEvent $event)
    {
        return isset($event->numberOfPoints) && $event->numberOfPoints > 0;
    }
}
