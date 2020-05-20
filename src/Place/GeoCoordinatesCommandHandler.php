<?php

namespace CultuurNet\UDB3\Place;

use CultuurNet\UDB3\Offer\AbstractGeoCoordinatesCommandHandler;
use CultuurNet\UDB3\Place\Commands\UpdateGeoCoordinatesFromAddress;

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
