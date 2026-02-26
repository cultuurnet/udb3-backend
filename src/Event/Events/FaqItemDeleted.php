<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Offer\Events\AbstractEvent;

final class FaqItemDeleted extends AbstractEvent
{
    public function __construct(string $itemId, public readonly string $faqItemId)
    {
        parent::__construct($itemId);
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'faq_item_id' => $this->faqItemId,
        ];
    }

    public static function deserialize(array $data): self
    {
        return new self($data['item_id'], $data['faq_item_id']);
    }
}
