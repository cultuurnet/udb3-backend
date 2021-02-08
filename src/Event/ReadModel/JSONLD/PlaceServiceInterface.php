<?php

namespace CultuurNet\UDB3\Event\ReadModel\JSONLD;

/**
 * Interface for components that can provide the JSON-LD representation
 * of a Place, which will be embedded as the location in the JSON-LD
 * representation of an Event.
 */
interface PlaceServiceInterface
{
    public function placeJSONLD(string $placeId): array;
}
