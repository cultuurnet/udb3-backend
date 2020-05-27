<?php

namespace CultuurNet\UDB3\Event;

use CultuurNet\UDB3\Event\Commands\UpdateGeoCoordinatesFromAddress;
use CultuurNet\UDB3\Offer\AbstractGeoCoordinatesCommandHandler;

class GeoCoordinatesCommandHandler extends AbstractGeoCoordinatesCommandHandler
{
    /**
     * @param UpdateGeoCoordinatesFromAddress $updateGeoCoordinates
     */
    public function handleUpdateGeoCoordinatesFromAddress(UpdateGeoCoordinatesFromAddress $updateGeoCoordinates)
    {
        $this->updateGeoCoordinatesFromAddress($updateGeoCoordinates);
    }
}
