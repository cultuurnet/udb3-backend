<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use CultureFeed_Cdb_Item_Event;
use CultuurNet\UDB3\Title;

trait EventFromUDB2
{
    public function toGranularEvents(): array
    {
        $granularEvents = [];
        $cultureFeedEvent = $this->getCultureFeedEvent();
        $details = $cultureFeedEvent->getDetails();
        $firstDetail = $details->getFirst();

        $granularEvents[] = new TitleUpdated($this->eventId, new Title($firstDetail->getTitle()));

        return $granularEvents;
    }


    private function getCultureFeedEvent(): CultureFeed_Cdb_Item_Event
    {
        $cdbXml = new \SimpleXMLElement(
            $this->cdbXml,
            0,
            false,
            $this->cdbXmlNamespaceUri
        );

        return CultureFeed_Cdb_Item_Event::parseFromCdbXml($cdbXml);
    }
}
