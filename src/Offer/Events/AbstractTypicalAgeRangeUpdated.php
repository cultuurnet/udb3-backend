<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Events;

use CultuurNet\UDB3\Model\ValueObject\Audience\AgeRange;

abstract class AbstractTypicalAgeRangeUpdated extends AbstractEvent
{
    protected AgeRange $typicalAgeRange;

    final public function __construct(string $id, AgeRange $typicalAgeRange)
    {
        parent::__construct($id);
        $this->typicalAgeRange = $typicalAgeRange;
    }

    public function getTypicalAgeRange(): AgeRange
    {
        return $this->typicalAgeRange;
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'typicalAgeRange' => $this->typicalAgeRange->toString(),
        ];
    }

    public static function deserialize(array $data): AbstractTypicalAgeRangeUpdated
    {
        return new static($data['item_id'], AgeRange::fromString($data['typicalAgeRange']));
    }
}
