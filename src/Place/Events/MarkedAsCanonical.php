<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Events;

use CultuurNet\UDB3\Place\PlaceEvent;

// This event is no longer actively used but needs to stay because it is persisted inside the event store.
final class MarkedAsCanonical extends PlaceEvent
{
    private string $duplicatedBy;

    /**
     * @var string[]
     */
    private array $duplicatesOfDuplicate;

    public function __construct(string $placeId, string $duplicatedBy, array $duplicatesOfDuplicate = [])
    {
        parent::__construct($placeId);
        $this->duplicatedBy = $duplicatedBy;
        $this->duplicatesOfDuplicate = $duplicatesOfDuplicate;
    }

    public function getDuplicatedBy(): string
    {
        return $this->duplicatedBy;
    }

    /**
     * @return string[]
     */
    public function getDuplicatesOfDuplicate(): array
    {
        return $this->duplicatesOfDuplicate;
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'duplicated_by' => $this->duplicatedBy,
            'duplicates_of_duplicate' => $this->duplicatesOfDuplicate,
        ];
    }

    public static function deserialize(array $data): MarkedAsCanonical
    {
        return new static($data['place_id'], $data['duplicated_by'], $data['duplicates_of_duplicate']);
    }
}
