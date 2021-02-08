<?php

namespace CultuurNet\UDB3\Offer;

use CultuurNet\UDB3\Facility;
use ValueObjects\StringLiteral\StringLiteral;

interface OfferFacilityResolverInterface
{
    public function byId(StringLiteral $facilityId): Facility;
}
