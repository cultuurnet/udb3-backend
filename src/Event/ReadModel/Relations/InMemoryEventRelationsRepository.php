<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\Relations;

/**
 * In-memory implementation of CultuurNet\UDB3\Event\ReadModel\Relations\RepositoryInterface, mostly useful in tests
 * to avoid having to mock complex data sets.
 */
final class InMemoryEventRelationsRepository implements RepositoryInterface
{
    private array $places = [];
    private array $organizers = [];

    public function storeRelations(string $eventId, ?string $placeId, ?string $organizerId): void
    {
        $this->places[$eventId] = $placeId;
        $this->organizers[$eventId] = $organizerId;
        $this->places = array_filter($this->places);
        $this->organizers = array_filter($this->organizers);
    }

    public function storeOrganizer(string $eventId, ?string $organizerId): void
    {
        $this->organizers[$eventId] = $organizerId;
        $this->organizers = array_filter($this->organizers);
    }

    public function storePlace(string $eventId, ?string $placeId): void
    {
        $this->places[$eventId] = $placeId;
        $this->places = array_filter($this->places);
    }

    public function removeOrganizer(string $eventId): void
    {
        unset($this->organizers[$eventId]);
    }

    public function getEventsLocatedAtPlace(string $placeId): array
    {
        return array_keys($this->places, $placeId, true);
    }

    public function getEventsOrganizedByOrganizer(string $organizerId): array
    {
        return array_keys($this->organizers, $organizerId, true);
    }

    public function getPlaceOfEvent(string $eventId): ?string
    {
        return $this->places[$eventId] ?? null;
    }

    public function getOrganizerOfEvent(string $eventId): ?string
    {
        return $this->organizers[$eventId] ?? null;
    }

    public function removeRelations(string $eventId): void
    {
        unset($this->places[$eventId], $this->organizers[$eventId]);
    }

}
