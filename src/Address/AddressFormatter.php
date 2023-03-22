<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Address;

interface AddressFormatter
{
    /**
     * @return string
     */
    public function format(Address $address);
}
