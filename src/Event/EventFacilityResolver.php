<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use CultuurNet\UDB3\Offer\OfferFacilityResolver;

class EventFacilityResolver extends OfferFacilityResolver
{
    final protected function getFacilities(): array
    {
        return [
            '3.23.2.0.0' => new Category(new CategoryID('3.23.2.0.0'), new CategoryLabel('Assistentie'), CategoryDomain::facility()),
            '3.13.1.0.0' => new Category(new CategoryID('3.13.1.0.0'), new CategoryLabel('Voorzieningen voor assistentiehonden'), CategoryDomain::facility()),
            '3.13.2.0.0' => new Category(new CategoryID('3.13.2.0.0'), new CategoryLabel('Audiodescriptie'), CategoryDomain::facility()),
            '3.17.1.0.0' => new Category(new CategoryID('3.17.1.0.0'), new CategoryLabel('Ringleiding'), CategoryDomain::facility()),
            '3.17.3.0.0' => new Category(new CategoryID('3.17.3.0.0'), new CategoryLabel('Boven- of ondertiteling'), CategoryDomain::facility()),
            '3.23.3.0.0' => new Category(new CategoryID('3.23.3.0.0'), new CategoryLabel('Rolstoel ter beschikking'), CategoryDomain::facility()),
            '3.25.0.0.0' => new Category(new CategoryID('3.25.0.0.0'), new CategoryLabel('Contactpunt voor personen met handicap'), CategoryDomain::facility()),
            '3.26.0.0.0' => new Category(new CategoryID('3.26.0.0.0'), new CategoryLabel('Parkeerplaats'), CategoryDomain::facility()),
            '3.27.0.0.0' => new Category(new CategoryID('3.27.0.0.0'), new CategoryLabel('Rolstoeltoegankelijk'), CategoryDomain::facility()),
            '3.28.0.0.0' => new Category(new CategoryID('3.28.0.0.0'), new CategoryLabel('Alternatieve ingang'), CategoryDomain::facility()),
            '3.29.0.0.0' => new Category(new CategoryID('3.29.0.0.0'), new CategoryLabel('Gegarandeerd zicht'), CategoryDomain::facility()),
            '3.30.0.0.0' => new Category(new CategoryID('3.30.0.0.0'), new CategoryLabel('Rolstoelpodium'), CategoryDomain::facility()),
            '3.32.0.0.0' => new Category(new CategoryID('3.32.0.0.0'), new CategoryLabel('Voorbehouden camping'), CategoryDomain::facility()),
            '3.31.0.0.0' => new Category(new CategoryID('3.31.0.0.0'), new CategoryLabel('Toegankelijk sanitair'), CategoryDomain::facility()),
            '3.33.0.0.0' => new Category(new CategoryID('3.33.0.0.0'), new CategoryLabel('Tolken Vlaamse Gebarentaal'), CategoryDomain::facility()),
            '3.34.0.0.0' => new Category(new CategoryID('3.34.0.0.0'), new CategoryLabel('Vereenvoudigde informatie'), CategoryDomain::facility()),
            '3.36.0.0.0' => new Category(new CategoryID('3.36.0.0.0'), new CategoryLabel('Verzorgingsruimte'), CategoryDomain::facility()),
            '3.37.0.0.0' => new Category(new CategoryID('3.37.0.0.0'), new CategoryLabel('Oplaadzone rolstoelen'), CategoryDomain::facility()),
            '3.38.0.0.0' => new Category(new CategoryID('3.38.0.0.0'), new CategoryLabel('Inter-assistentie'), CategoryDomain::facility()),
            '3.39.0.0.0' => new Category(new CategoryID('3.39.0.0.0'), new CategoryLabel('Begeleiderspas'), CategoryDomain::facility()),
            '3.40.0.0.0' => new Category(new CategoryID('3.40.0.0.0'), new CategoryLabel('Inter-events'), CategoryDomain::facility()),
            'H28fcfRKFQAQs00K9NF9hh' => new Category(new CategoryID('H28fcfRKFQAQs00K9NF9hh'), new CategoryLabel('Prikkelarm aanbod'), CategoryDomain::facility()),
            '4Vz9eZf0cnQmtfqcGGnNMF' => new Category(new CategoryID('4Vz9eZf0cnQmtfqcGGnNMF'), new CategoryLabel('Afspraken en voorspelbaarheid'), CategoryDomain::facility()),
        ];
    }
}
