<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Event\ReadModel\JSONLD;

/**
 * Interface for components that can provide the JSON-LD representation
 * of a Place, which will be embedded as the location in the JSON-LD
 * representation of an Event.
 */
interface PlaceServiceInterface
{
    /**
     * Gets the JSON-LD structure of a place.
     *
     * @param string $placeId
     *   Id of the Place.
     *
     * @return \stdClass
     */
    public function placeJSONLD($placeId);
}
