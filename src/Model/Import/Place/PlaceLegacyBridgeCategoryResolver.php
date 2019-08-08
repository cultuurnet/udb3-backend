<?php

namespace CultuurNet\UDB3\Model\Import\Place;

use CultuurNet\UDB3\Place\PlaceFacilityResolver;
use CultuurNet\UDB3\Place\PlaceThemeResolver;
use CultuurNet\UDB3\Place\PlaceTypeResolver;
use CultuurNet\UDB3\Model\Import\Taxonomy\Category\LegacyBridgeCategoryResolver;

class PlaceLegacyBridgeCategoryResolver extends LegacyBridgeCategoryResolver
{
    public function __construct()
    {
        parent::__construct(new PlaceTypeResolver(), new PlaceThemeResolver(), new PlaceFacilityResolver());
    }
}
