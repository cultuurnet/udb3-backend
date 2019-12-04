<?php

namespace CultuurNet\UDB3\Event;

use CultuurNet\UDB3\Organizer\OrganizerRelationServiceInterface;
use CultuurNet\UDB3\Event\ReadModel\Relations\RepositoryInterface;

class EventOrganizerRelationService implements OrganizerRelationServiceInterface
{
    /**
     * @var EventEditingServiceInterface
     */
    private $editingService;

    /**
     * @var RepositoryInterface
     */
    private $relationsRepository;

    /**
     * @param EventEditingServiceInterface $editingService
     * @param RepositoryInterface $relationsRepository
     */
    public function __construct(
        EventEditingServiceInterface $editingService,
        RepositoryInterface $relationsRepository
    ) {
        $this->editingService = $editingService;
        $this->relationsRepository = $relationsRepository;
    }

    /**
     * @param string $organizerId
     */
    public function deleteOrganizer($organizerId)
    {
        $eventIds = $this->relationsRepository->getEventsOrganizedByOrganizer($organizerId);

        foreach ($eventIds as $eventId) {
            $this->editingService->deleteOrganizer($eventId, $organizerId);
        }
    }
}
