<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Events;

use CultuurNet\UDB3\Place\PlaceEvent;

// This event is no longer actively used but needs to stay because it is persisted inside the event store.
final class MarkedAsDuplicate extends PlaceEvent
{
    private string $duplicateOf;

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
