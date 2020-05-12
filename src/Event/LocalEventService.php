<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Event;

use Broadway\Repository\RepositoryInterface;
use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
use CultuurNet\UDB3\Event\ReadModel\Relations\RepositoryInterface as RelationsRepository;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\LocalEntityService;

class LocalEventService extends LocalEntityService implements EventServiceInterface
{
    /**
     * @var RepositoryInterface
     */
    protected $eventRelationsRepository;

    public function __construct(
        DocumentRepositoryInterface $documentRepository,
        RepositoryInterface $eventRepository,
        RelationsRepository $eventRelationsRepository,
        IriGeneratorInterface $iriGenerator
    ) {
        parent::__construct($documentRepository, $eventRepository, $iriGenerator);
        $this->eventRelationsRepository = $eventRelationsRepository;
    }

    /**
     * Get a single event by its id.
     *
     * @deprecated
     *   Use getEntity() instead.
     *
     * @param string $id
     *   A string uniquely identifying an event.
     *
     * @return array
     *   An event array.
     *
     * @throws EventNotFoundException if an event can not be found for the given id
     */
    public function getEvent($id)
    {
        try {
            return $this->getEntity($id);
        } catch (EntityNotFoundException $e) {
            throw new EventNotFoundException(
                "Event with id: {$id} not found"
            );
        }
    }

    /**
     * @param string $organizerId
     * @return string[]
     */
    public function eventsOrganizedByOrganizer($organizerId)
    {
        return $this->eventRelationsRepository->getEventsOrganizedByOrganizer($organizerId);
    }

    /**
     * @param string $placeId
     * @return string[]
     */
    public function eventsLocatedAtPlace($placeId)
    {
        return $this->eventRelationsRepository->getEventsLocatedAtPlace($placeId);
    }
}
