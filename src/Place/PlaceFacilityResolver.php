<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use CultuurNet\UDB3\Offer\OfferFacilityResolver;

class PlaceFacilityResolver extends OfferFacilityResolver
{
    final protected function getFacilities(): array
    {
        return [
            '3.13.1.0.0' => new Category(new CategoryID('3.13.1.0.0'), new CategoryLabel('Voorzieningen voor assistentiehonden'), CategoryDomain::facility()),
            '3.23.3.0.0' => new Category(new CategoryID('3.23.3.0.0'), new CategoryLabel('Rolstoel ter beschikking'), CategoryDomain::facility()),
            '3.25.0.0.0' => new Category(new CategoryID('3.25.0.0.0'), new CategoryLabel('Contactpunt voor personen met handicap'), CategoryDomain::facility()),
            '3.26.0.0.0' => new Category(new CategoryID('3.26.0.0.0'), new CategoryLabel('Parkeerplaats'), CategoryDomain::facility()),
        ];
    }
}
