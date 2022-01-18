<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

use CultuurNet\UDB3\Offer\OfferIdentifierCollection;
use CultuurNet\UDB3\Offer\IriOfferIdentifier;

class Results
{
    private OfferIdentifierCollection $items;

    private int $totalItems;

    public function __construct(OfferIdentifierCollection $items, int $totalItems)
    {
        $this->items = $items;
        $this->totalItems = $totalItems;
    }

    /**
     * @return IriOfferIdentifier[]
     */
    public function getItems(): array
    {
        return $this->items->toArray();
    }

    public function getTotalItems(): int
    {
        return $this->totalItems;
    }
}
