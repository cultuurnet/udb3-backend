<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Address\CultureFeed;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;

class CultureFeedAddressFactory implements CultureFeedAddressFactoryInterface
{
    public function fromCdbAddress(\CultureFeed_Cdb_Data_Address_PhysicalAddress $cdbAddress): Address
    {
        $requiredFields = [
            'street' => $cdbAddress->getStreet(),
            'zip code' => $cdbAddress->getZip(),
            'city' => $cdbAddress->getCity(),
            'country' => $cdbAddress->getCountry(),
        ];

        $missingFields = [];
        foreach ($requiredFields as $key => $requiredField) {
            if (is_null($requiredField)) {
                $missingFields[] = $key;
            }
        }

        if (count($missingFields) > 0) {
            $keys = implode(', ', $missingFields);
            throw new \InvalidArgumentException('The given cdbxml address is missing a ' . $keys);
        }


        return new Address(
            new Street($cdbAddress->getStreet() . ' ' . $cdbAddress->getHouseNumber()),
            new PostalCode($cdbAddress->getZip()),
            new Locality($cdbAddress->getCity()),
            new CountryCode($cdbAddress->getCountry())
        );
    }
}
