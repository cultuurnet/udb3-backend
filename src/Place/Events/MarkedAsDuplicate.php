<?php

namespace CultuurNet\UDB3\Place\Events;

use CultuurNet\UDB3\Place\PlaceEvent;

final class MarkedAsDuplicate extends PlaceEvent
{
    /**
     * @var string
     */
    private $duplicateOf;

    public function __construct(string $placeId, string $duplicateOf)
    {
        parent::__construct($placeId);
        $this->duplicateOf = $duplicateOf;
    }

    public function getDuplicateOf(): string
    {
        return $this->duplicateOf;
    }

    public function serialize(): array
    {
        return parent::serialize() + [
                'duplicate_of' => $this->duplicateOf,
            ];
    }

    public static function deserialize(array $data): MarkedAsDuplicate
    {
        return new static($data['place_id'], ($data['duplicate_of']));
    }
}
