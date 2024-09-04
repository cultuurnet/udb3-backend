<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Canonical;

class PlaceWithCluster
{
    private string $clusterId;
    private string $placeUuid;
    private ?string $canonical;

    public function __construct(string $clusterId, string $placeUuid, string $canonical = null)
    {
        $this->clusterId = $clusterId;
        $this->placeUuid = $placeUuid;
        $this->canonical = $canonical;
    }

    public function getClusterId(): string
    {
        return $this->clusterId;
    }

    public function getPlaceUuid(): string
    {
        return $this->placeUuid;
    }

    public function getCanonical(): ?string
    {
        return $this->canonical;
    }
}
