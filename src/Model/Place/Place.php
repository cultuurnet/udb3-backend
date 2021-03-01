<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Place;

use CultuurNet\UDB3\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Model\Offer\Offer;
use CultuurNet\UDB3\Model\ValueObject\Geography\TranslatedAddress;

interface Place extends Offer
{
    /**
     * @return TranslatedAddress
     */
    public function getAddress();

    /**
     * @return Coordinates|null
     */
    public function getGeoCoordinates();

    /**
     * @return bool
     *   Dummy locations are no real places in UDB3 and have no place id.
     *   They were locations that were imported from older systems.
     *   They use place id 00000000-0000-0000-0000-000000000000.
     */
    public function isDummyLocation();
}
