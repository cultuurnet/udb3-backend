<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use CultureFeed_Cdb_Data_EventDetail;
use CultureFeed_Cdb_Item_Event;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Language;
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

        $details->next();
        while ($details->valid()) {
            /** @var CultureFeed_Cdb_Data_EventDetail $detail */
            $detail = $details->current();
            $granularEvents[] = new TitleTranslated(
                $this->eventId,
                new Language($detail->getLanguage()),
                new Title($detail->getTitle())
            );
            $details->next();
        }

        /* @var \Culturefeed_Cdb_Data_Category $category */
        foreach ($cultureFeedEvent->getCategories() as $category) {
            if ($category->getType() === 'eventtype') {
                $granularEvents[] = new TypeUpdated(
                    $this->eventId,
                    new EventType($category->getId(), $category->getName())
                );
            }
        }

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
