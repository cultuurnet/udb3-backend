<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Canonical;

interface DuplicatePlaceRepository
{
    /**
     * @return int[]
     */
    public function getClusterIds(): array;

    /**
     * @return string[]
     */
    public function getPlacesInCluster(int $clusterId): array;

    public function setCanonicalOnCluster(int $clusterId, string $canonical): void;

    public function getCanonicalOfPlace(string $placeId): ?string;

    public function getDuplicatesOfPlace(string $placeId): ?array;
}
