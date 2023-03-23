<?php

namespace CultuurNet\UDB3\Address;

interface AddressParser
{
    public function parse(string $formattedAddress): ?ParsedAddress;
}
