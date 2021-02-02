<?php

namespace CultuurNet\UDB3\Offer\Events\Moderation;

use CultuurNet\UDB3\Offer\Events\AbstractEvent;
use ValueObjects\StringLiteral\StringLiteral;

abstract class AbstractRejected extends AbstractEvent
{
    /**
     * @var StringLiteral
     */
    private $reason;

    final public function __construct(string $itemId, StringLiteral $reason)
    {
        parent::__construct($itemId);
        $this->reason = $reason;
    }

    public function getReason(): StringLiteral
    {
        return $this->reason;
    }

    public function serialize(): array
    {
        return parent::serialize() + array(
            'reason' => $this->reason->toNative(),
        );
    }

    public static function deserialize(array $data): AbstractRejected
    {
        return new static(
            $data['item_id'],
            new StringLiteral($data['reason'])
        );
    }
}
