<?php

namespace CultuurNet\UDB3\Place;

use CultuurNet\UDB3\Facility;
use CultuurNet\UDB3\Offer\OfferFacilityResolver;

class PlaceFacilityResolver extends OfferFacilityResolver
{
    /**
     * @inheritdoc
     */
    final protected function getFacilities()
    {
        return [
            "3.13.1.0.0" => new Facility("3.13.1.0.0", "Voorzieningen voor assistentiehonden"),
            "3.23.3.0.0" => new Facility("3.23.3.0.0", "Rolstoel ter beschikking"),
            "3.25.0.0.0" => new Facility("3.25.0.0.0", "Contactpunt voor personen met handicap"),
            "3.26.0.0.0" => new Facility("3.26.0.0.0", "Parkeerplaats"),
        ];
    }
}
