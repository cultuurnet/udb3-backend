<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Canonical;

final class PlaceCluster
{
    private int $clusterId;

    private array $placeIds;

    public function __construct(int $clusterId, array $placeIds)
    {
        $this->clusterId = $clusterId;
        $this->placeIds = $placeIds;
    }

    public function getClusterId(): int
    {
        return $this->clusterId;
    }

    public function getPlacesIds(): array
    {
        return $this->placeIds;
    }
}
