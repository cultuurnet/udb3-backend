<?php

namespace CultuurNet\UDB3\Event\ReadModel\JSONLD;

/**
 * Interface for components that can provide the JSON-LD representation
 * of an Organizer, which will be embedded in the JSON-LD representation
 * of an Event.
 */
interface OrganizerServiceInterface
{
    public function organizerJSONLD(string $organizerId): array;
}
