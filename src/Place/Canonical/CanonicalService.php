<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Canonical;

interface CanonicalService
{
    public function getCanonical(string $clusterId): string;

    /**
     * @param string[] $placeIds
     * This function is only used by LookupDuplicatePlaceWithSapi3 to make sure
     * we always return an id. Therefore, it does not throw exceptions and
     * should probably not be used anywhere else.
     */
    public function getCanonicalFromArrayWithoutThrowing(array $placeIds): string;
}
