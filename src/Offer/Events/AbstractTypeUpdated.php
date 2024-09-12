<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Events;

use CultuurNet\UDB3\Event\EventType;

abstract class AbstractTypeUpdated extends AbstractEvent
{
    protected EventType $type;

    final public function __construct(string $itemId, EventType $type)
    {
        parent::__construct($itemId);
        $this->type = $type;
    }

    public function getType(): EventType
    {
        return $this->type;
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'type' => $this->type->serialize(),
        ];
    }

    public static function deserialize(array $data): AbstractTypeUpdated
    {
        return new static($data['item_id'], EventType::deserialize($data['type']));
    }
}
