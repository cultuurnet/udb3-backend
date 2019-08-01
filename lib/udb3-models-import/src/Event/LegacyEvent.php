<?php

namespace CultuurNet\UDB3\Model\Import\Event;

use CultuurNet\UDB3\Event\ValueObjects\AudienceType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Model\Import\Offer\LegacyOffer;

interface LegacyEvent extends LegacyOffer
{
    /**
     * @return LocationId
     */
    public function getLocation(): LocationId;

    /**
     * @return AudienceType
     */
    public function getAudienceType();
}
