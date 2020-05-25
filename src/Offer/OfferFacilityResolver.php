<?php

namespace CultuurNet\UDB3\Offer;

use CultuurNet\UDB3\Facility;
use ValueObjects\StringLiteral\StringLiteral;

abstract class OfferFacilityResolver implements OfferFacilityResolverInterface
{
    /**
     * @var Facility[]
     */
    private $facilities;

    /**
     * PlaceTypeResolver constructor.
     */
    public function __construct()
    {
        $this->facilities = $this->getFacilities();
    }

    /**
     * @inheritdoc
     */
    public function byId(StringLiteral $facilityId)
    {
        if (!array_key_exists((string) $facilityId, $this->facilities)) {
            throw new \Exception("Unknown facility id '{$facilityId}'");
        }

        return $this->facilities[(string) $facilityId];
    }

    /**
     * @return Facility[]
     */
    abstract protected function getFacilities();
}
