<?php

namespace CultuurNet\UDB3\Address;

class DefaultAddressFormatter implements AddressFormatterInterface
{
    /**
     * @param Address $address
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
