<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\Relations;

interface RepositoryInterface
{
    public function storeRelations(string $eventId, ?string $placeId, ?string $organizerId): void;
    public function storeOrganizer(string $eventId, ?string $organizerId): void;
    public function storePlace(string $eventId, ?string $placeId): void;
    public function removeOrganizer(string $eventId): void;
    public function getEventsLocatedAtPlace(string $placeId): array;
    public function getEventsOrganizedByOrganizer(string $organizerId): array;
    public function getPlaceOfEvent(string $eventId): ?string;
    public function getOrganizerOfEvent(string $eventId): ?string;
    public function removeRelations(string $eventId): void;
}
