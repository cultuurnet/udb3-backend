<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use CultuurNet\UDB3\Facility;
use CultuurNet\UDB3\StringLiteral;

interface OfferFacilityResolverInterface
{
    public function byId(StringLiteral $facilityId): Facility;
}
