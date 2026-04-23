<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Model\ValueObject\Audience\BirthdateRange;
use CultuurNet\UDB3\Offer\Events\AbstractEvent;

final class BirthdateRangeUpdated extends AbstractEvent
{
    public function __construct(string $itemId, public readonly BirthdateRange $birthdateRange)
    {
        parent::__construct($itemId);
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'birthdateRange' => $this->birthdateRange->toArray(),
        ];
    }

    public static function deserialize(array $data): self
    {
        return new self($data['item_id'], BirthdateRange::fromArray($data['birthdateRange']));
    }
}
