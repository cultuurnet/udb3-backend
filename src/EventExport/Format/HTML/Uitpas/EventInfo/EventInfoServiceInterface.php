<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\EventInfo;

interface EventInfoServiceInterface
{
    /**
     * @param string $eventId
     * @return EventInfo
     */
    public function getEventInfo($eventId);
}
