<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Offer\Events\AbstractEvent;

final class ChildrenOnlyUpdated extends AbstractEvent
{
    public function __construct(string $itemId, public readonly bool $childrenOnly)
    {
        parent::__construct($itemId);
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'children_only' => $this->childrenOnly,
        ];
    }

    public static function deserialize(array $data): self
    {
        return new self($data['item_id'], (bool) $data['children_only']);
    }
}
