<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Events;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\SerializableSimpleXmlElement;

trait PlaceFromUDB2
{
    public function toGranularEvents(): array
    {
        $granularEvents = [];
        $placeAsArray = $this->getPlaceAsArray();
        $details = $placeAsArray['actordetails'][0]['actordetail'];

        foreach ($details as $key => $detail) {
            if ($key == 0) {
                $granularEvents[] = new TitleUpdated($this->actorId, $detail['title'][0]['_text']);
            } else {
                $granularEvents[] = new TitleTranslated(
                    $this->actorId,
                    new Language($detail['@attributes']['lang']),
                    $detail['title'][0]['_text']
                );
            }
        }

        if (isset($placeAsArray['contactinfo'][0]['address'][0]['physical'][0])) {
            $addressFromXml = $placeAsArray['contactinfo'][0]['address'][0]['physical'][0];
            $granularEvents[] = new AddressUpdated(
                $this->actorId,
                new Address(
                    new Street($this->getStreet($addressFromXml)),
                    new PostalCode($addressFromXml['zipcode'][0]['_text']),
                    new Locality($addressFromXml['city'][0]['_text']),
                    new CountryCode($addressFromXml['country'][0]['_text'])
                )
            );
        }

        return $granularEvents;
    }

    private function getPlaceAsArray(): array
    {
        $cdbXml = new SerializableSimpleXmlElement(
            $this->cdbXml,
            0,
            false,
            $this->cdbXmlNamespaceUri
        );
        $actorAsArray = $cdbXml->serialize();
        // Some cdbxml have a root node 'cdbxml'
        if (array_key_first($actorAsArray) === 'cdbxml') {
            return $actorAsArray['cdbxml']['actor'][0];
        }
        return $actorAsArray['actor'];
    }

    private function getStreet(array $addressFromXml): string
    {
        if (!isset($addressFromXml['street'][0]['_text'])) {
            return '';
        }
        if (!isset($addressFromXml['housenr'][0]['_text'])) {
            return $addressFromXml['street'][0]['_text'];
        }
        return $addressFromXml['street'][0]['_text'] . ' ' . $addressFromXml['housenr'][0]['_text'];
    }
}
