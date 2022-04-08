<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Canonical;

interface DuplicatePlaceRepository
{
    public function getClusterIds(): array;

    public function getCluster(int $clusterId): PlaceCluster;
}
