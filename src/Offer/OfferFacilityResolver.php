<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use CultuurNet\UDB3\Facility;

abstract class OfferFacilityResolver implements OfferFacilityResolverInterface
{
    /**
     * @var Facility[]
     */
    private array $facilities;

    public function __construct()
    {
        $this->facilities = $this->getFacilities();
    }

    public function byId(string $facilityId): Facility
    {
        if (!array_key_exists($facilityId, $this->facilities)) {
            throw new \Exception("Unknown facility id '{$facilityId}'");
        }

        return $this->facilities[$facilityId];
    }

    /**
     * @return Facility[]
     */
    abstract protected function getFacilities();
}
