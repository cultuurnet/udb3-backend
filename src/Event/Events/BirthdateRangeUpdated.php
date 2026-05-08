<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Model\Serializer\ValueObject\Audience\BirthdateRangeDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Audience\BirthdateRangeNormalizer;
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
            'birthdateRange' => (new BirthdateRangeNormalizer())->normalize($this->birthdateRange),
        ];
    }

    public static function deserialize(array $data): self
    {
        return new self(
            $data['item_id'],
            (new BirthdateRangeDenormalizer())->denormalize($data['birthdateRange'], BirthdateRange::class)
        );
    }
}
