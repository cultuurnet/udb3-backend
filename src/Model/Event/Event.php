<?php

namespace CultuurNet\UDB3\Model\Event;

use CultuurNet\UDB3\Model\Offer\Offer;
use CultuurNet\UDB3\Model\Place\PlaceReference;
use CultuurNet\UDB3\Model\ValueObject\Audience\AudienceType;

interface Event extends Offer
{
    /**
     * @return AudienceType
     */
    public function getAudienceType();

    /**
     * @return PlaceReference
     */
    public function getPlaceReference();
}
