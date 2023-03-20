<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Events;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Title;
use CultuurNet\UDB3\SerializableXML;

trait PlaceFromUDB2
{
    public function toGranularEvents(): array
    {
        $granularEvents = [];
        $placeAsArray = $this->getPlaceAsArray();
        $details = $placeAsArray['actor']['actordetails'][0]['actordetail'];

        foreach ($details as $key => $detail) {
            if ($key == 0) {
                $granularEvents[] = new TitleUpdated($this->actorId, new Title($detail['title'][0]['_text']));
            } else {
                $granularEvents[] = new TitleTranslated(
                    $this->actorId,
                    new Language($detail['@attributes']['lang']),
                    new Title($detail['title'][0]['_text'])
                );
            }
        }

        $addressFromXml = $placeAsArray['actor']['contactinfo'][0]['address'][0]['physical'][0];

        $granularEvents[] = new AddressUpdated(
            $this->actorId,
            new Address(
                new Street($addressFromXml['street'][0]['_text'] . ' ' . $addressFromXml['housenr'][0]['_text']),
                new PostalCode($addressFromXml['zipcode'][0]['_text']),
                new Locality($addressFromXml['city'][0]['_text']),
                new CountryCode($addressFromXml['country'][0]['_text'])
            )
        );

        return $granularEvents;
    }

    public function getPlaceAsArray(): array
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
