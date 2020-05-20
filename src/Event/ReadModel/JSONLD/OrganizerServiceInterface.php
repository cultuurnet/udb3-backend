<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Event\ReadModel\JSONLD;

/**
 * Interface for components that can provide the JSON-LD representation
 * of an Organizer, which will be embedded in the JSON-LD representation
 * of an Event.
 */
interface OrganizerServiceInterface
{
    /**
     * Gets the JSON-LD structure of an Organizer.
     *
     * @param string $organizerId
     *   Id of the Organizer.
     *
     * @return \stdClass
     */
    public function organizerJSONLD($oganizerId);
}
