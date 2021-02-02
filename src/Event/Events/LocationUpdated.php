<?php

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Offer\Events\AbstractEvent;

final class LocationUpdated extends AbstractEvent
{
    /**
     * @var LocationId
     */
    private $locationId;

    public function __construct(
        string $eventId,
        LocationId $locationId
    ) {
        parent::__construct($eventId);

        $this->locationId = $locationId;
    }

    public function getLocationId(): LocationId
    {
        return $this->locationId;
    }

    public function serialize(): array
    {
        return parent::serialize() + [
                'location_id' => $this->locationId->toNative(),
            ];
    }

    public static function deserialize(array $data): LocationUpdated
    {
        return new self(
            $data['item_id'],
            new LocationId($data['location_id'])
        );
    }
}
