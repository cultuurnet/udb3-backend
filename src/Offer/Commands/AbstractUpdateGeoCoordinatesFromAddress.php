<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Address\Address;

abstract class AbstractUpdateGeoCoordinatesFromAddress extends AbstractCommand
{
    private Address $address;

    /**
     * @param string $itemId
     */
    public function __construct($itemId, Address $address)
    {
        parent::__construct($itemId);
        $this->address = $address;
    }

    public function getAddress(): Address
    {
        return $this->address;
    }
}
