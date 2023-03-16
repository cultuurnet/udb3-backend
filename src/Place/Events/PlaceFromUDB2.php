<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Events;

use CultureFeed_Cdb_Data_ActorDetail;
use CultureFeed_Cdb_Data_Address;
use CultureFeed_Cdb_Item_Actor;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Title;

trait PlaceFromUDB2
{
    public function toGranularEvents(): array
    {
        $granularEvents = [];
        $cultureFeedActor = $this->getCultureFeedActor();
        $details = $cultureFeedActor->getDetails();
        $firstDetail = $details->getFirst();

        $granularEvents[] = new TitleUpdated($this->actorId, new Title($firstDetail->getTitle()));

        $details->next();
        while ($details->valid()) {
            /** @var CultureFeed_Cdb_Data_ActorDetail $detail */
            $detail = $details->current();
            $granularEvents[] = new TitleTranslated(
                $this->actorId,
                new Language($detail->getLanguage()),
                new Title($detail->getTitle())
            );
            $details->next();
        }
        /** @var CultureFeed_Cdb_Data_Address $address */
        $address = $cultureFeedActor->getContactInfo()->getAddresses()[0];
        $physicalAddress = $address->getPhysicalAddress();

        $granularEvents[] = new AddressUpdated(
            $this->actorId,
            new Address(
                new Street($physicalAddress->getStreet() . ' ' . $physicalAddress->getHouseNumber()),
                new PostalCode($physicalAddress->getZip()),
                new Locality($physicalAddress->getCity()),
                new CountryCode($physicalAddress->getCountry())
            )
        );

        return $granularEvents;
    }

    private function getCultureFeedActor(): CultureFeed_Cdb_Item_Actor
    {
        $cdbXml = new \SimpleXMLElement(
            $this->cdbXml,
            0,
            false,
            $this->cdbXmlNamespaceUri
        );

        return CultureFeed_Cdb_Item_Actor::parseFromCdbXml($cdbXml);
    }
}
