<?php

namespace CultuurNet\UDB3\Event\ReadModel\JSONLD;

use Broadway\EventHandling\EventListenerInterface;
use CultuurNet\UDB3\Event\EventServiceInterface;
use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;
use CultuurNet\UDB3\Offer\IriOfferIdentifierFactoryInterface;
use CultuurNet\UDB3\Organizer\OrganizerProjectedToJSONLD;
use CultuurNet\UDB3\OrganizerService;
use CultuurNet\UDB3\Place\Events\PlaceProjectedToJSONLD;
use CultuurNet\UDB3\PlaceService;
use ValueObjects\Web\Url;

class RelatedEventLDProjector implements EventListenerInterface
{
    use DelegateEventHandlingToSpecificMethodTrait;

    /**
     * @var PlaceService
     */
    protected $placeService;

    /**
     * @var OrganizerServiceInterface
     */
    protected $organizerService;

    /**
     * @var EventServiceInterface
     */
    protected $eventService;

    /**
     * @var IriOfferIdentifierFactoryInterface
     */
    protected $iriOfferIdentifierFactory;

    /**
     * @param DocumentRepositoryInterface $repository
     * @param EventServiceInterface $eventService
     * @param PlaceService $placeService
     * @param OrganizerService $organizerService
     */
    public function __construct(
        DocumentRepositoryInterface $repository,
        EventServiceInterface $eventService,
        PlaceService $placeService,
        OrganizerService $organizerService,
        IriOfferIdentifierFactoryInterface $iriOfferIdentifierFactory
    ) {
        $this->repository = $repository;
        $this->eventService = $eventService;
        $this->placeService = $placeService;
        $this->organizerService = $organizerService;


        $this->iriOfferIdentifierFactory = $iriOfferIdentifierFactory;
    }

    protected function applyOrganizerProjectedToJSONLD(
        OrganizerProjectedToJSONLD $organizerProjectedToJSONLD
    ) {
        $eventIds = $this->eventService->eventsOrganizedByOrganizer(
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

        $eventsLocatedAtPlace = $this->eventService->eventsLocatedAtPlace(
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
        $document = $this->repository->get($eventId);

        if (!$document) {
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
