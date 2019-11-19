<?php

namespace CultuurNet\UDB3\Place;

use CultuurNet\UDB3\Organizer\OrganizerRelationServiceInterface;
use CultuurNet\UDB3\Place\ReadModel\Relations\RepositoryInterface;

class PlaceOrganizerRelationService implements OrganizerRelationServiceInterface
{
    /**
     * @var PlaceEditingServiceInterface
     */
    private $editingService;

    /**
     * @var RepositoryInterface
     */
    private $relationsRepository;

    /**
     * @param PlaceEditingServiceInterface $editingService
     * @param RepositoryInterface $relationsRepository
     */
    public function __construct(
        PlaceEditingServiceInterface $editingService,
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
        $placeIds = $this->relationsRepository->getPlacesOrganizedByOrganizer($organizerId);

        foreach ($placeIds as $placeId) {
            $this->editingService->deleteOrganizer($placeId, $organizerId);
        }
    }
}
