<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Address\Parser;

interface AddressParser
{
    public function parse(string $formattedAddress): ?ParsedAddress;
}
