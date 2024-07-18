<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Canonical;

interface DuplicatePlaceRepository
{
    /**
     * @return string[]
     */
    public function getClusterIds(): array;

    /**
     * @return string[]
     */
    public function getPlacesInCluster(string $clusterId): array;

    public function setCanonicalOnCluster(string $clusterId, string $canonical): void;

    public function getCanonicalOfPlace(string $placeId): ?string;

    public function getDuplicatesOfPlace(string $placeId): ?array;

    public function markAsProcessed(string $placeId): void;
}
