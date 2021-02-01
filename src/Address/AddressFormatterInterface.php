<?php

namespace CultuurNet\UDB3\Address;

interface AddressFormatterInterface
{
    /**
     * @param Address $address
     * @return string
     */
    public function format(Address $address);
}
