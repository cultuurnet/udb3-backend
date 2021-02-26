<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use CultuurNet\UDB3\Collection\AbstractCollection;

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
