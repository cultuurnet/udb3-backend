<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Address;

use CultuurNet\UDB3\Model\ValueObject\Geography\Address as Udb3AddressModel;

interface AddressFormatter
{
    public function format(Udb3AddressModel $address): string;
}
