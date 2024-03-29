<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\Relations;

/**
 * In-memory implementation of CultuurNet\UDB3\Place\ReadModel\Relations\PlaceRelationsRepository, mostly useful in
 * tests to avoid having to mock complex data sets.
 */
final class InMemoryPlaceRelationsRepository implements PlaceRelationsRepository
{
    private array $organizers = [];

    public function storeRelations(string $placeId, ?string $organizerId): void
    {
        $this->organizers[$placeId] = $organizerId;
        $this->organizers = array_filter($this->organizers);
    }

    public function removeRelations(string $placeId): void
    {
        unset($this->organizers[$placeId]);
    }

    public function getPlacesOrganizedByOrganizer(string $organizerId): array
    {
        return array_keys($this->organizers, $organizerId, true);
    }
}
