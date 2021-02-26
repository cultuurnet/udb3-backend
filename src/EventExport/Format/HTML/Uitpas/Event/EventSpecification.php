<?php

namespace CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\Event;

interface EventSpecification
{
    /**
     * @return bool
     */
    public function isSatisfiedBy(\CultureFeed_Uitpas_Event_CultureEvent $event);
}
