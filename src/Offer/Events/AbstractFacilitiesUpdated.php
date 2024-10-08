<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Events;

use CultuurNet\UDB3\Facility;

abstract class AbstractFacilitiesUpdated extends AbstractEvent
{
    protected array $facilities;

    final public function __construct(string $id, array $facilities)
    {
        parent::__construct($id);
        $this->facilities = $facilities;
    }

    public function getFacilities(): array
    {
        return $this->facilities;
    }

    public static function deserialize(array $data): AbstractFacilitiesUpdated
    {
        $facilities = [];
        foreach ($data['facilities'] as $facility) {
            $facilities[] = Facility::deserialize($facility);
        }

        return new static($data['item_id'], $facilities);
    }

    public function serialize(): array
    {
        $facilities = [];
        foreach ($this->facilities as $facility) {
            $facilities[] = $facility->serialize();
        }

        return parent::serialize() + [
            'facilities' => $facilities,
        ];
    }
}
