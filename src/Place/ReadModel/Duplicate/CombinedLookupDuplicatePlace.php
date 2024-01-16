<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\Duplicate;

use CultuurNet\UDB3\Model\Place\Place;

class CombinedLookupDuplicatePlace implements LookupDuplicatePlace
{
    private array $lookups;

    public function __construct(LookupDuplicatePlace ...$lookups)
    {
        $this->lookups = $lookups;
    }

    public function getDuplicatePlaceUri(Place $place): ?string
    {
        foreach ($this->lookups as $lookup) {
            $duplicatePlaceId = $lookup->getDuplicatePlaceUri($place);
            if ($duplicatePlaceId !== null) {
                return $duplicatePlaceId;
            }
        }

        return null;
    }
}
