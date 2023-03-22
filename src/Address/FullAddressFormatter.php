<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Address;

final class FullAddressFormatter implements AddressFormatter
{
    public function format(Address $address): string
    {
        return implode(', ', [
            $address->getStreetAddress()->toNative(),
            $address->getPostalCode()->toNative() . ' ' . $address->getLocality()->toNative(),
            $address->getCountryCode()->toString(),
        ]);
    }
}
