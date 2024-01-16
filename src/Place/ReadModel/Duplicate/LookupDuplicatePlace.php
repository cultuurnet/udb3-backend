<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\Duplicate;

use CultuurNet\UDB3\Model\Place\Place;

interface LookupDuplicatePlace
{
    public function getDuplicatePlaceUri(Place $place): ?string;
}
