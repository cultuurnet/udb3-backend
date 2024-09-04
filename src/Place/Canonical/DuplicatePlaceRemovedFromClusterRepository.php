<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Canonical;

interface DuplicatePlaceRemovedFromClusterRepository
{
    public function addPlace(string $placeId): void;

    public function getAllPlaces(): array;

    public function truncateTable(): void;
}
