<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use CultuurNet\UDB3\Model\ValueObject\Collection\Collection;

class OfferIdentifierCollection extends Collection
{
    public function __construct(IriOfferIdentifier ...$offerIdentifiers)
    {
        parent::__construct(...$offerIdentifiers);
    }
}
