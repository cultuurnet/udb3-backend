<?php

namespace CultuurNet\UDB3\Address;

interface CultureFeedAddressFactoryInterface
{
    /**
     * @param \CultureFeed_Cdb_Data_Address_PhysicalAddress $cdbAddress
     * @return Address
     */
    public function fromCdbAddress(\CultureFeed_Cdb_Data_Address_PhysicalAddress $cdbAddress);
}
