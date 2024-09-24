<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\Model\ValueObject\Geography\Address;

final class UpdateGeoCoordinatesFromAddress
{
    private string $organizerId;

    private Address $address;

    public function __construct(string $organizerId, Address $address)
    {
        $this->organizerId = $organizerId;
        $this->address = $address;
    }

    public function organizerId(): string
    {
        return $this->organizerId;
    }

    public function address(): Address
    {
        return $this->address;
    }
}
