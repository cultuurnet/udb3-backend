<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Address;

class DefaultAddressFormatter implements AddressFormatterInterface
{
    /**
     * @return string
     */
    public function format(Address $address)
    {
        return $address->getStreetAddress() . ', ' .
            $address->getPostalCode() . ' ' .
            $address->getLocality() . ', ' .
            $address->getCountry()->getCode();
    }
}
