<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\SerializableXML;
use CultuurNet\UDB3\Title;

trait EventFromUDB2
{
    public function toGranularEvents(): array
    {
        $granularEvents = [];
        $eventAsArray = $this->getEventAsArray();
        $details = $eventAsArray['event']['eventdetails'][0]['eventdetail'];

        foreach ($details as $key => $detail) {
            if ($key == 0) {
                $granularEvents[] = new TitleUpdated($this->eventId, new Title($detail['title'][0]['_text']));
            } else {
                $granularEvents[] = new TitleTranslated(
                    $this->eventId,
                    new Language($detail['@attributes']['lang']),
                    new Title($detail['title'][0]['_text'])
                );
            }
        }

        $categories = $eventAsArray['event']['categories'][0]['category'];

        foreach ($categories as $category) {
            if ($category['@attributes']['type'] === 'eventtype') {
                $granularEvents[] = new TypeUpdated(
                    $this->eventId,
                    new EventType($category['@attributes']['catid'], $category['_text'])
                );
            }
        }

        return $granularEvents;
    }

    private function getEventAsArray(): array
    {
        $cdbXml = new SerializableXML(
            $this->cdbXml,
            0,
            false,
            $this->cdbXmlNamespaceUri
        );

        return $cdbXml->serialize();
    }
}
