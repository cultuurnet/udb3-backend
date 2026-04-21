<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Model\ValueObject\Audience\BirthYearRange;
use CultuurNet\UDB3\Offer\Events\AbstractEvent;

final class TypicalBirthYearRangeUpdated extends AbstractEvent
{
    public function __construct(string $itemId, public readonly BirthYearRange $typicalBirthYearRange)
    {
        parent::__construct($itemId);
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'typicalBirthYearRange' => $this->typicalBirthYearRange->toString(),
        ];
    }

    public static function deserialize(array $data): self
    {
        return new self($data['item_id'], BirthYearRange::fromString($data['typicalBirthYearRange']));
    }
}
