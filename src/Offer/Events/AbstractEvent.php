<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Events;

use Broadway\Serializer\Serializable;

abstract class AbstractEvent implements Serializable
{
    /**
     * @var string
     */
    protected $itemId;

    public function __construct(string $itemId)
    {
        if (!is_string($itemId)) {
            throw new \InvalidArgumentException(
                'Expected itemId to be a string, received ' . gettype($itemId)
            );
        }

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
