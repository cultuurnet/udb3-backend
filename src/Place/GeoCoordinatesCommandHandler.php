<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use CultuurNet\UDB3\Offer\AbstractGeoCoordinatesCommandHandler;
use CultuurNet\UDB3\Place\Commands\UpdateGeoCoordinatesFromAddress;

class GeoCoordinatesCommandHandler extends AbstractGeoCoordinatesCommandHandler
{
    public function handleUpdateGeoCoordinatesFromAddress(UpdateGeoCoordinatesFromAddress $updateGeoCoordinates): void
    {
        $this->updateGeoCoordinatesFromAddress($updateGeoCoordinates);
    }
}
