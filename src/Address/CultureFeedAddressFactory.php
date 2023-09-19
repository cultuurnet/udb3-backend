<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Address;

use CultuurNet\UDB3\Model\ValueObject\Geography\Address as Udb3AddressModel;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality as Udb3LocalityModel;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode as Udb3PostalCodeModel;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street as Udb3StreetModel;

class CultureFeedAddressFactory implements CultureFeedAddressFactoryInterface
{
    public function fromCdbAddress(\CultureFeed_Cdb_Data_Address_PhysicalAddress $cdbAddress): Udb3AddressModel
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

        return new Udb3AddressModel(
            new Udb3StreetModel($cdbAddress->getStreet() . ' ' . $cdbAddress->getHouseNumber()),
            new Udb3PostalCodeModel($cdbAddress->getZip()),
            new Udb3LocalityModel($cdbAddress->getCity()),
            new CountryCode($cdbAddress->getCountry())
        );
    }
}
