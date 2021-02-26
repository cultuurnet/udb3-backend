<?php

namespace CultuurNet\UDB3\Address;

interface AddressFormatterInterface
{
    /**
     * @return string
     */
    public function format(Address $address);
}
