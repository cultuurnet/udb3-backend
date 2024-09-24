<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Address\CultureFeed;

use CultuurNet\UDB3\Address\Address;

interface CultureFeedAddressFactoryInterface
{
    public function fromCdbAddress(\CultureFeed_Cdb_Data_Address_PhysicalAddress $cdbAddress): Address;
}
