<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Address;

interface CultureFeedAddressFactoryInterface
{
    public function fromCdbAddress(\CultureFeed_Cdb_Data_Address_PhysicalAddress $cdbAddress): Address;
}
