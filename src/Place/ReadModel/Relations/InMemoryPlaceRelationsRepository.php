<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\Relations;

/**
 * In-memory implementation of CultuurNet\UDB3\Place\ReadModel\Relations\RepositoryInterface, mostly useful in tests
 * to avoid having to mock complex data sets.
 */
final class InMemoryPlaceRelationsRepository implements RepositoryInterface
{
    private array $organizers = [];

    public function storeRelations(string $placeId, string $organizerId): void
    {
        $this->organizers[$placeId] = $organizerId;
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
