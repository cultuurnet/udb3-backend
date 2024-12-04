<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;

interface OfferFacilityResolverInterface
{
    public function byId(string $facilityId): Category;
}
