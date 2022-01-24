<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

use CultuurNet\UDB3\Model\ValueObject\Identity\ItemIdentifier;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemIdentifiers;

class Results
{
    private ItemIdentifiers $items;

    private int $totalItems;

    public function __construct(ItemIdentifiers $items, int $totalItems)
    {
        $this->items = $items;
        $this->totalItems = $totalItems;
    }

    /**
     * @return ItemIdentifier[]
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
