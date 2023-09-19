<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Address;

use CultuurNet\UDB3\Model\ValueObject\Geography\Address as Udb3AddressModel;

final class FullAddressFormatter implements AddressFormatter
{
    private const LINE_SEPARATOR = ', ';

    public function format(Udb3AddressModel $address): string
    {
        return implode(
            self::LINE_SEPARATOR,
            array_filter(
                [
                    $address->getStreet()->toString(),
                    $address->getPostalCode()->toString() . ' ' . $address->getLocality()->toString(),
                    $address->getCountryCode()->toString(),
                ]
            )
        );
    }
}
