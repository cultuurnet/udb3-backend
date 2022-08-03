<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\Relations;

interface RepositoryInterface
{
    public function storeRelations(string $placeId, ?string $organizerId): void;
    public function removeRelations(string $placeId): void;
    public function getPlacesOrganizedByOrganizer(string $organizerId): array;
}
