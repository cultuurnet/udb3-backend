<?php

namespace CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\Event;

interface EventSpecification
{
    /**
     * @param \CultureFeed_Uitpas_Event_CultureEvent $event
     * @return bool
     */
    public function isSatisfiedBy(\CultureFeed_Uitpas_Event_CultureEvent $event);
}
