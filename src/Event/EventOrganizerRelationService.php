<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use CultuurNet\UDB3\Organizer\OrganizerRelationServiceInterface;
use CultuurNet\UDB3\Event\ReadModel\Relations\EventRelationsRepository;

class EventOrganizerRelationService implements OrganizerRelationServiceInterface
{
    /**
     * @var EventEditingServiceInterface
     */
    private $editingService;

    /**
     * @var EventRelationsRepository
     */
    private $relationsRepository;


    public function __construct(
        EventEditingServiceInterface $editingService,
        EventRelationsRepository $relationsRepository
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
