<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Events;

use Broadway\Serializer\Serializable;

abstract class AbstractEvent implements Serializable
{
    protected string $itemId;

    public function __construct(string $itemId)
    {

        $this->itemId = $itemId;
    }

    public function getItemId(): string
    {
        return $this->itemId;
    }

    public function serialize(): array
    {
        return [
            'item_id' => $this->itemId,
        ];
    }
}
