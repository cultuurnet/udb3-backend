<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\JSONLD;

use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Event\ReadModel\Relations\RepositoryInterface;
use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;
use CultuurNet\UDB3\Offer\IriOfferIdentifierFactoryInterface;
use CultuurNet\UDB3\Organizer\OrganizerProjectedToJSONLD;
use CultuurNet\UDB3\OrganizerService;
use CultuurNet\UDB3\Place\Events\PlaceProjectedToJSONLD;
use CultuurNet\UDB3\Place\LocalPlaceService;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use ValueObjects\Web\Url;

class RelatedEventLDProjector implements EventListener
{
    use DelegateEventHandlingToSpecificMethodTrait;

    /**
     * @var DocumentRepository
     */
    private $repository;

    /**
     * @var LocalPlaceService
     */
    protected $placeService;

    /**
     * @var OrganizerService
     */
    protected $organizerService;


    /**
     * @var IriOfferIdentifierFactoryInterface
     */
    protected $iriOfferIdentifierFactory;
    private RepositoryInterface $relationsRepository;


    public function __construct(
        DocumentRepository $repository,
        RepositoryInterface $relationsRepository,
        LocalPlaceService $placeService,
        OrganizerService $organizerService,
        IriOfferIdentifierFactoryInterface $iriOfferIdentifierFactory
    ) {
        $this->repository = $repository;
        $this->relationsRepository = $relationsRepository;
        $this->placeService = $placeService;
        $this->organizerService = $organizerService;
        $this->iriOfferIdentifierFactory = $iriOfferIdentifierFactory;
    }

    protected function applyOrganizerProjectedToJSONLD(
        OrganizerProjectedToJSONLD $organizerProjectedToJSONLD
    ): void {
        $eventIds = $this->relationsRepository->getEventsOrganizedByOrganizer(
            $organizerProjectedToJSONLD->getId()
        );

        $organizer = $this->organizerService->getEntity(
            $organizerProjectedToJSONLD->getId()
        );

        $organizerJSONLD = json_decode($organizer);

        foreach ($eventIds as $eventId) {
            $this->updateEmbeddedOrganizer($eventId, $organizerJSONLD);
        }
    }

    protected function applyPlaceProjectedToJSONLD(
        PlaceProjectedToJSONLD $placeProjectedToJSONLD
    ) {
        $identifier = $this->iriOfferIdentifierFactory->fromIri(
            Url::fromNative($placeProjectedToJSONLD->getIri())
        );

        $eventsLocatedAtPlace = $this->relationsRepository->getEventsLocatedAtPlace(
            $placeProjectedToJSONLD->getItemId()
        );

        $placeJSONLDString = $this->placeService->getEntity(
            $identifier->getId()
        );
        $placeJSONLD = json_decode($placeJSONLDString);

        foreach ($eventsLocatedAtPlace as $eventId) {
            $this->updatedEmbeddedLocation($eventId, $placeJSONLD);
        }
    }

    private function updateEmbeddedOrganizer($eventId, $organizerJSONLD)
    {
        $this->updateJSONLD(
            $eventId,
            function ($eventLd) use ($organizerJSONLD) {
                $eventLd->organizer = $organizerJSONLD;
            }
        );
    }

    private function updatedEmbeddedLocation($eventId, $placeJSONLD)
    {
        $this->updateJSONLD(
            $eventId,
            function ($eventLd) use ($placeJSONLD) {
                $eventLd->location = $placeJSONLD;
            }
        );
    }

    private function updateJSONLD($eventId, $callback)
    {
        try {
            $document = $this->repository->fetch($eventId);
        } catch (DocumentDoesNotExist $e) {
            return;
        }

        $eventLD = $document->getBody();

        $newEventLD = clone $eventLD;

        $callback($newEventLD);

        if ($newEventLD == $eventLD) {
            return;
        }

        $document = $document->withBody($newEventLD);

        $this->repository->save($document);
    }
}
