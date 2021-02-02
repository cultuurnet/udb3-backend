<?php

namespace CultuurNet\UDB3\Offer;

use TwoDotsTwice\Collection\AbstractCollection;

/**
 * @method OfferIdentifierCollection with($item)
 * @method IriOfferIdentifier[] toArray()
 */
class OfferIdentifierCollection extends AbstractCollection
{
    protected function getValidObjectType()
    {
        return IriOfferIdentifier::class;
    }
}
