<?php

namespace CultuurNet\UDB3\Address;

class LocalityAddressFormatter implements AddressFormatterInterface
{
    /**
     * @inheritdoc
     */
    public function format(Address $address)
    {
        return $address->getPostalCode() . ' ' .
            $address->getLocality() . ', ' .
            $address->getCountry()->getCode();
    }
}
