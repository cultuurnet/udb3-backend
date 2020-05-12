<?php

namespace CultuurNet\UDB3\Event;

use CultuurNet\UDB3\Facility;
use CultuurNet\UDB3\Offer\OfferFacilityResolver;

class EventFacilityResolver extends OfferFacilityResolver
{
    /**
     * @inheritdoc
     */
    final protected function getFacilities()
    {
        return [
            "3.23.2.0.0" => new Facility("3.23.2.0.0", "Assistentie"),
            "3.13.1.0.0" => new Facility("3.13.1.0.0", "Voorzieningen voor assistentiehonden"),
            "3.13.2.0.0" => new Facility("3.13.2.0.0", "Audiodescriptie"),
            "3.17.1.0.0" => new Facility("3.17.1.0.0", "Ringleiding"),
            "3.17.2.0.0" => new Facility("3.17.2.0.0", "Voelstoelen"),
            "3.17.3.0.0" => new Facility("3.17.3.0.0", "Boven- of ondertiteling"),
            "3.23.3.0.0" => new Facility("3.23.3.0.0", "Rolstoel ter beschikking"),
            "3.25.0.0.0" => new Facility("3.25.0.0.0", "Contactpunt voor personen met handicap"),
            "3.26.0.0.0" => new Facility("3.26.0.0.0", "Parkeerplaats"),
            "3.27.0.0.0" => new Facility("3.27.0.0.0", "Rolstoeltoegankelijk"),
            "3.28.0.0.0" => new Facility("3.28.0.0.0", "Alternatieve ingang"),
            "3.29.0.0.0" => new Facility("3.29.0.0.0", "Gegarandeerd zicht"),
            "3.30.0.0.0" => new Facility("3.30.0.0.0", "Rolstoelpodium"),
            "3.32.0.0.0" => new Facility("3.32.0.0.0", "Voorbehouden camping"),
            "3.31.0.0.0" => new Facility("3.31.0.0.0", "Toegankelijk sanitair"),
            "3.33.0.0.0" => new Facility("3.33.0.0.0", "Tolken Vlaamse Gebarentaal"),
            "3.34.0.0.0" => new Facility("3.34.0.0.0", "Vereenvoudigde informatie"),
            "3.35.0.0.0" => new Facility("3.35.0.0.0", "1 begeleider gratis"),
            "3.36.0.0.0" => new Facility("3.36.0.0.0", "Verzorgingsruimte"),
            "3.37.0.0.0" => new Facility("3.37.0.0.0", "Oplaadzone rolstoelen"),
            "3.38.0.0.0" => new Facility("3.38.0.0.0", "Inter-assistentie"),
            "3.39.0.0.0" => new Facility("3.39.0.0.0", "Begeleiderspas"),
            "3.40.0.0.0" => new Facility("3.40.0.0.0", "Inter-events"),
        ];
    }
}
