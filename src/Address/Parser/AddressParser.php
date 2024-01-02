<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Address\Parser;

use CultuurNet\UDB3\Address\ParsedAddress;

interface AddressParser
{
    public function parse(string $formattedAddress): ?ParsedAddress;
}
