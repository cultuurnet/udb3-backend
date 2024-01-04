<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Address\Formatter;

use CultuurNet\UDB3\Address\Address;

interface AddressFormatter
{
    public function format(Address $address): string;
}
