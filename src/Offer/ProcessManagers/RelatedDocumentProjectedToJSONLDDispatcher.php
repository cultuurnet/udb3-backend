<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ProcessManagers;

use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventBus;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Event\Events\EventProjectedToJSONLD;
use CultuurNet\UDB3\Event\ReadModel\Relations\EventRelationsRepository;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Organizer\OrganizerProjectedToJSONLD;
use CultuurNet\UDB3\Place\Events\PlaceProjectedToJSONLD;
use CultuurNet\UDB3\Place\ReadModel\Relations\PlaceRelationsRepository;

final class RelatedDocumentProjectedToJSONLDDispatcher implements EventListener
{
    private const METADATA_KEY = 'embeds_updated';

    private EventBus $eventBus;
    private EventRelationsRepository $eventRelationsRepository;
    private PlaceRelationsRepository $placeRelationsRepository;
    private IriGeneratorInterface $eventIriGenerator;
    private IriGeneratorInterface $placeIriGenerator;

    public function __construct(
        EventBus $eventBus,
        EventRelationsRepository $eventRelationsRepository,
        PlaceRelationsRepository $placeRelationsRepository,
        IriGeneratorInterface $eventIriGenerator,
        IriGeneratorInterface $placeIriGenerator
    ) {
        $this->eventBus = $eventBus;
        $this->eventRelationsRepository = $eventRelationsRepository;
        $this->placeRelationsRepository = $placeRelationsRepository;
        $this->eventIriGenerator = $eventIriGenerator;
        $this->placeIriGenerator = $placeIriGenerator;
    }

    public static function hasDispatchedMessage(DomainMessage $domainMessage): bool
    {
        return $domainMessage->getMetadata()->get(self::METADATA_KEY) === true;
    }

    /**
     * @uses handlePlaceProjectedToJSONLD
     * @uses handleOrganizerProjectedToJSONLD
     */
    public function handle(DomainMessage $domainMessage): void
    {
        $payload = $domainMessage->getPayload();
        $payloadClassName = get_class($payload);

        $handlers = [
            PlaceProjectedToJSONLD::class => 'handlePlaceProjectedToJSONLD',
            OrganizerProjectedToJSONLD::class => 'handleOrganizerProjectedToJSONLD',
        ];

        $handler = $handlers[$payloadClassName] ?? null;
        if ($handler) {
            $this->{$handler}($payload);
        }
    }

    private function handlePlaceProjectedToJSONLD(PlaceProjectedToJSONLD $placeProjectedToJSONLD): void
    {
        $placeId = $placeProjectedToJSONLD->getItemId();

        // In theory the event relations repository should only return unique event ids for a given place id, but to be
        // safe pass it through array_unique() so we don't needlessly dispatch duplicate messages.
        $eventIds = $this->eventRelationsRepository->getEventsLocatedAtPlace($placeId);
        $eventIds = array_unique($eventIds);

        $eventMessages = $this->convertEventIdsToEventMessages($eventIds);
        $this->eventBus->publish($eventMessages);
    }

    private function handleOrganizerProjectedToJSONLD(OrganizerProjectedToJSONLD $organizerProjectedToJSONLD): void
    {
        $organizerId = $organizerProjectedToJSONLD->getId();

        $placeIds = $this->placeRelationsRepository->getPlacesOrganizedByOrganizer($organizerId);
        $placeIds = array_unique($placeIds);

        // We need a flat list of all event ids related to the places that are related to the updated organizer, but to
        // avoid using array_merge() in a loop which consumes a lot of memory, we keep a list of arrays with event ids
        // for each place and then merge it after the loop using array_merge() with the splat operator.
        $placeEventIdLists = [];
        foreach ($placeIds as $placeId) {
            $eventIdsLocatedAtPlace = $this->eventRelationsRepository->getEventsLocatedAtPlace($placeId);
            $placeEventIdLists[] = $eventIdsLocatedAtPlace;
        }

        $eventIds = $this->eventRelationsRepository->getEventsOrganizedByOrganizer($organizerId);
        $eventIds = array_merge($eventIds, ...$placeEventIdLists);

        // Make sure we don't needlessly dispatch duplicate messages.
        // An event can be directly related to an organizer, and to a place that is related to the same organizer.
        // So usually a lot of event ids are duplicates.
        $eventIds = array_unique($eventIds);

        $placeMessages = $this->convertPlaceIdsToEventMessages($placeIds);
        $eventMessages = $this->convertEventIdsToEventMessages($eventIds);

        $stream = new DomainEventStream(
            array_merge(
                $placeMessages->getIterator()->getArrayCopy(),
                $eventMessages->getIterator()->getArrayCopy(),
            )
        );
        $this->eventBus->publish($stream);
    }

    private function convertEventIdsToEventMessages(array $eventIds): DomainEventStream
    {
        return new DomainEventStream(
            array_map(fn (string $eventId) => $this->createEventUpdatedMessage($eventId), $eventIds)
        );
    }

    private function convertPlaceIdsToEventMessages(array $placeIds): DomainEventStream
    {
        return new DomainEventStream(
            array_map(fn (string $placeId) => $this->createPlaceUpdatedMessage($placeId), $placeIds)
        );
    }

    private function createEventUpdatedMessage(string $eventId): DomainMessage
    {
        return $this->createNewDomainMessage(
            $eventId,
            new EventProjectedToJSONLD($eventId, $this->eventIriGenerator->iri($eventId))
        );
    }

    private function createPlaceUpdatedMessage(string $placeId): DomainMessage
    {
        return $this->createNewDomainMessage(
            $placeId,
            new PlaceProjectedToJSONLD($placeId, $this->placeIriGenerator->iri($placeId))
        );
    }

    private function createNewDomainMessage(string $id, $event): DomainMessage
    {
        return DomainMessage::recordNow(
            $id,
            0,
            new Metadata([self::METADATA_KEY => true]),
            $event
        );
    }
}
