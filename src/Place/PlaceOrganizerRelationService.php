<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use CultuurNet\UDB3\Organizer\OrganizerRelationServiceInterface;
use CultuurNet\UDB3\Place\ReadModel\Relations\PlaceRelationsRepository;

class PlaceOrganizerRelationService implements OrganizerRelationServiceInterface
{
    /**
     * @var PlaceEditingServiceInterface
     */
    private $editingService;

    /**
     * @var PlaceRelationsRepository
     */
    private $relationsRepository;


    public function __construct(
        PlaceEditingServiceInterface $editingService,
        PlaceRelationsRepository $relationsRepository
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
