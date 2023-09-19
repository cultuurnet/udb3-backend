<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Address;

use CultuurNet\UDB3\Model\ValueObject\Geography\Address as Udb3AddressModel;

interface CultureFeedAddressFactoryInterface
{
    /**
     * @return Udb3AddressModel
     */
    public function fromCdbAddress(\CultureFeed_Cdb_Data_Address_PhysicalAddress $cdbAddress);
}
