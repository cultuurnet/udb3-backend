<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\Event;

use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Model\Event\Event;
use CultuurNet\UDB3\Model\Import\Offer\Udb3ModelToLegacyOfferAdapter;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;

/**
 * @deprecated Should no longer be used because all commands should use the VOs from the Model namespace.
 */
class Udb3ModelToLegacyEventAdapter extends Udb3ModelToLegacyOfferAdapter implements LegacyEvent
{
    private Uuid $placeId;

    public function __construct(Event $event)
    {
        $placeId = $event->getPlaceReference()->getPlaceId();
        $this->placeId = $placeId;
    }

    public function getLocation(): LocationId
    {
        return new LocationId($this->placeId->toString());
    }
}
