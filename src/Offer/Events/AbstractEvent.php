<?php

namespace CultuurNet\UDB3\Offer\Events;

use Broadway\Serializer\SerializableInterface;

abstract class AbstractEvent implements SerializableInterface
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
        return array(
            'item_id' => $this->itemId,
        );
    }
}
