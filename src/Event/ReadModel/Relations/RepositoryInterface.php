<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Event\ReadModel\Relations;

interface RepositoryInterface
{
    /**
     * @param string $eventId
     * @param string $placeId
     * @param string $organizerId
     */
    public function storeRelations($eventId, $placeId, $organizerId);

    /**
     * @param string $eventId
     * @param string $organizerId
     */
    public function storeOrganizer($eventId, $organizerId);

    /**
     * @param string $eventId
     * @param string $placeId
     */
    public function storePlace($eventId, $placeId);

    /**
     * @param string $eventId
     */
    public function removeOrganizer($eventId);

    /**
     * @param string $placeId
     *
     * @return string[]
     *  A list of event Ids.
     */
    public function getEventsLocatedAtPlace($placeId);

    /**
     * @param string $organizerId
     *
     * @return string[]
     *  A list of event Ids.
     */
    public function getEventsOrganizedByOrganizer($organizerId);

    /**
     * @param string $eventId
     * @return string|null
     */
    public function getPlaceOfEvent($eventId);

    /**
     * @param string $eventId
     * @return string|null
     */
    public function getOrganizerOfEvent($eventId);

    /**
     * Remove all the relations that are linked to this event.
     *
     * @param string $eventId
     */
    public function removeRelations($eventId);
}
