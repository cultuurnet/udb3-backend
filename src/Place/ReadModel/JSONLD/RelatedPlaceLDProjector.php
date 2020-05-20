<?php

namespace CultuurNet\UDB3\Place\ReadModel\JSONLD;

use Broadway\EventHandling\EventListenerInterface;
use CultuurNet\UDB3\EntityServiceInterface;
use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;
use CultuurNet\UDB3\Organizer\OrganizerProjectedToJSONLD;
use CultuurNet\UDB3\Place\ReadModel\Relations\RepositoryInterface;

class RelatedPlaceLDProjector implements EventListenerInterface
{
    use DelegateEventHandlingToSpecificMethodTrait;

    /**
     * @var RepositoryInterface
     */
    private $placeRelations;

    /**
     * @var DocumentRepositoryInterface
     */
    private $repository;

    /**
     * @var EntityServiceInterface
     */
    private $organizerService;

    /**
     * @param DocumentRepositoryInterface $repository
     * @param EntityServiceInterface $organizerService
     * @param RepositoryInterface $placeRelations
     */
    public function __construct(
        DocumentRepositoryInterface $repository,
        EntityServiceInterface $organizerService,
        RepositoryInterface $placeRelations
    ) {
        $this->repository = $repository;
        $this->organizerService = $organizerService;
        $this->placeRelations = $placeRelations;
    }

    /**
     * @param OrganizerProjectedToJSONLD $organizerProjectedToJSONLD
     *
     * @throws \CultuurNet\UDB3\EntityNotFoundException
     */
    protected function applyOrganizerProjectedToJSONLD(
        OrganizerProjectedToJSONLD $organizerProjectedToJSONLD
    ) {
        $placeIds = $this->placeRelations->getPlacesOrganizedByOrganizer(
            $organizerProjectedToJSONLD->getId()
        );

        $organizer = $this->organizerService->getEntity(
            $organizerProjectedToJSONLD->getId()
        );

        $organizerJSONLD = json_decode($organizer);

        foreach ($placeIds as $placeId) {
            $this->updateEmbeddedOrganizer($placeId, $organizerJSONLD);
        }
    }

    private function updateEmbeddedOrganizer(string $placeId, $organizerJSONLD)
    {
        $document = $this->repository->get($placeId);

        if (!$document) {
            return;
        }

        $placeLD = $document->getBody();

        $newPlaceLD = clone $placeLD;
        $newPlaceLD->organizer = $organizerJSONLD;

        if ($newPlaceLD == $placeLD) {
            return;
        }

        $this->repository->save($document->withBody($newPlaceLD));
    }
}
