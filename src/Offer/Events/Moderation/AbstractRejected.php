<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Events\Moderation;

use CultuurNet\UDB3\Offer\Events\AbstractEvent;

abstract class AbstractRejected extends AbstractEvent
{
    private string $reason;

    final public function __construct(string $itemId, string $reason)
    {
        parent::__construct($itemId);
        $this->reason = $reason;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'reason' => $this->reason,
        ];
    }

    public static function deserialize(array $data): AbstractRejected
    {
        return new static(
            $data['item_id'],
           $data['reason']
        );
    }
}
