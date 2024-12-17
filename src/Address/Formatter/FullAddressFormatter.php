<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Address\Formatter;

use CultuurNet\UDB3\Model\ValueObject\Geography\Address;

final class FullAddressFormatter implements AddressFormatter
{
    private const LINE_SEPARATOR = ', ';

    public function format(Address $address): string
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
