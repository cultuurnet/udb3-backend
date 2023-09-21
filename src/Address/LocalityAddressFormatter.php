<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Address;

final class LocalityAddressFormatter implements AddressFormatter
{
    public function format(Address $address): string
    {
        return $address->getPostalCode()->toNative() . ' ' .
            $address->getLocality() . ', ' .
            $address->getCountryCode()->toString();
    }
}
