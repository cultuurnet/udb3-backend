<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\Address\Address;

class UpdateGeoCoordinatesFromAddress
{
    /**
     * @var string
     */
    private $organizerId;

    /**
     * @var Address
     */
    private $address;

    public function __construct(string $organizerId, Address $address)
    {
        $this->organizerId = $organizerId;
        $this->address = $address;
    }

    /**
     * @return string
     */
    public function organizerId(): string
    {
        return $this->organizerId;
    }

    /**
     * @return Address
     */
    public function address(): Address
    {
        return $this->address;
    }
}
