<?php

namespace CultuurNet\UDB3\Search;

use CultuurNet\UDB3\Offer\OfferIdentifierCollection;
use CultuurNet\UDB3\Offer\IriOfferIdentifier;
use ValueObjects\Number\Integer as IntegerLiteral;

class Results
{
    /**
     * @var OfferIdentifierCollection
     */
    private $items;

    /**
     * @var IntegerLiteral
     */
    private $totalItems;

    public function __construct(OfferIdentifierCollection $items, IntegerLiteral $totalItems)
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

    public function getTotalItems(): IntegerLiteral
    {
        return $this->totalItems;
    }
}
