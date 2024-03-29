<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\Place;

use CultuurNet\UDB3\Place\PlaceFacilityResolver;
use CultuurNet\UDB3\Place\PlaceTypeResolver;
use CultuurNet\UDB3\Model\Import\Taxonomy\Category\LegacyBridgeCategoryResolver;

class PlaceCategoryResolver extends LegacyBridgeCategoryResolver
{
    public function __construct()
    {
        parent::__construct(new PlaceTypeResolver(), new PlaceFacilityResolver(), null);
    }
}
