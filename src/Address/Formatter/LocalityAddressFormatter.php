<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Address\Formatter;

use CultuurNet\UDB3\Model\ValueObject\Geography\Address;

final class LocalityAddressFormatter implements AddressFormatter
{
    public function format(Address $address): string
    {
        return $address->getPostalCode()->toString() . ' ' .
            $address->getLocality()->toString() . ', ' .
            $address->getCountryCode()->toString();
    }
}
