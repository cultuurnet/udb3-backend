<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\MockupHelper;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;

class CreateLegacyAddress
{
    public function create(): Address
    {
        return new Address(
            new Street('Wetstraat 1'),
            new PostalCode('1000'),
            new Locality('Bxl'),
            new CountryCode('BE')
        );
    }
}
