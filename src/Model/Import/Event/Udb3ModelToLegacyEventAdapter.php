<?php

namespace CultuurNet\UDB3\Model\Import\Event;

use CultuurNet\UDB3\Event\ValueObjects\AudienceType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Model\Event\Event;
use CultuurNet\UDB3\Model\Import\Offer\Udb3ModelToLegacyOfferAdapter;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;

class Udb3ModelToLegacyEventAdapter extends Udb3ModelToLegacyOfferAdapter implements LegacyEvent
{
    /**
     * @var Event
     */
    private $event;

    /**
     * @var UUID
     */
    private $placeId;


    public function __construct(Event $event)
    {
        $placeId = $event->getPlaceReference()->getPlaceId();

        parent::__construct($event);
        $this->event = $event;
        $this->placeId = $placeId;
    }

    public function getLocation(): LocationId
    {
        return new LocationId($this->placeId->toString());
    }

    /**
     * @inheritdoc
     */
    public function getAudienceType()
    {
        $audienceType = $this->event->getAudienceType();
        return AudienceType::fromNative($audienceType->toString());
    }
}
