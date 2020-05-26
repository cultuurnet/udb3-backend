<?php

namespace CultuurNet\UDB3\Search;

use CultuurNet\UDB3\Offer\OfferIdentifierCollection;
use CultuurNet\UDB3\Offer\IriOfferIdentifier;
use ValueObjects\Number\Integer;

class Results
{
    /**
     * @var OfferIdentifierCollection
     */
    private $items;

    /**
     * @var Integer
     */
    private $totalItems;

    /**
     * @param OfferIdentifierCollection $items
     * @param \ValueObjects\Number\Integer $totalItems
     */
    public function __construct(OfferIdentifierCollection $items, Integer $totalItems)
    {
        $this->items = $items;
        $this->totalItems = $totalItems;
    }

    /**
     * @return IriOfferIdentifier[]
     */
    public function getItems()
    {
        return $this->items->toArray();
    }

    /**
     * @return \ValueObjects\Number\Integer
     */
    public function getTotalItems()
    {
        return $this->totalItems;
    }
}
