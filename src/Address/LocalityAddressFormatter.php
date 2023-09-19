<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Address;

use CultuurNet\UDB3\Model\ValueObject\Geography\Address as Udb3AddressModel;

final class LocalityAddressFormatter implements AddressFormatter
{
    public function format(Udb3AddressModel $address): string
    {
        return $address->getPostalCode()->toString() . ' ' .
            $address->getLocality()->toString() . ', ' .
            $address->getCountryCode()->toString();
    }
}
