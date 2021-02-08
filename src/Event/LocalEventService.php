<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Event;

use Broadway\Repository\RepositoryInterface;
use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\Event\ReadModel\Relations\RepositoryInterface as RelationsRepository;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\LocalEntityService;
use CultuurNet\UDB3\ReadModel\DocumentRepository;

class LocalEventService extends LocalEntityService implements EventServiceInterface
{
    /**
     * @var RelationsRepository
     */
    protected $eventRelationsRepository;

    public function __construct(
        DocumentRepository $documentRepository,
        RepositoryInterface $eventRepository,
        RelationsRepository $eventRelationsRepository,
        IriGeneratorInterface $iriGenerator
    ) {
        parent::__construct($documentRepository, $eventRepository, $iriGenerator);
        $this->eventRelationsRepository = $eventRelationsRepository;
    }

    /**
     * @deprecated Use getEntity() instead.
     */
    public function getEvent(string $id): string
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
