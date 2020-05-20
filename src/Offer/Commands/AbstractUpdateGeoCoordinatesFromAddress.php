<?php

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Address\Address;

abstract class AbstractUpdateGeoCoordinatesFromAddress extends AbstractCommand
{
    /**
     * @var Address
     */
    private $address;

    /**
     * @param string $itemId
     * @param Address $address
     */
    public function __construct($itemId, Address $address)
    {
        parent::__construct($itemId);
        $this->address = $address;
    }

    /**
     * @return Address
     */
    public function getAddress()
    {
        return $this->address;
    }
}
