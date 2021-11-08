<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use Broadway\Repository\Repository;
use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\Event\ReadModel\Relations\RepositoryInterface as RelationsRepository;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\LocalEntityService;
use CultuurNet\UDB3\ReadModel\DocumentRepository;

class LocalEventService extends LocalEntityService implements EventServiceInterface
{
    protected RelationsRepository $eventRelationsRepository;

    public function __construct(
        DocumentRepository $documentRepository,
        Repository $eventRepository,
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
     * @return string[]
     */
    public function eventsOrganizedByOrganizer(string $organizerId): array
    {
        return $this->eventRelationsRepository->getEventsOrganizedByOrganizer($organizerId);
    }

    /**
     * @return string[]
     */
    public function eventsLocatedAtPlace(string $placeId): array
    {
        return $this->eventRelationsRepository->getEventsLocatedAtPlace($placeId);
    }
}
