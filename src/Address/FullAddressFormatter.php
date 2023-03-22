<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Address;

final class FullAddressFormatter implements AddressFormatter
{
    public function format(Address $address): string
    {
        return $address->getStreetAddress() . ', ' .
            $address->getPostalCode() . ' ' .
            $address->getLocality() . ', ' .
            $address->getCountryCode()->toString();
    }
}
