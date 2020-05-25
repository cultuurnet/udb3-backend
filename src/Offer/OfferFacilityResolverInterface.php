<?php

namespace CultuurNet\UDB3\Offer;

use CultuurNet\UDB3\Category;
use ValueObjects\StringLiteral\StringLiteral;

interface OfferFacilityResolverInterface
{
    /**
     * @param StringLiteral $typeId
     * @return Category
     * @throws \Exception
     */
    public function byId(StringLiteral $typeId);
}
