<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\History;

use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Event\Events\EventCopied;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\LocationUpdated;
use CultuurNet\UDB3\Event\ReadModel\Relations\EventLocationHistoryRepository;
use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;
use CultuurNet\UDB3\Model\Place\PlaceIDParser;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use Psr\Log\LoggerInterface;

class EventPlaceHistoryProjector implements EventListener
{
    use DelegateEventHandlingToSpecificMethodTrait;

    private EventLocationHistoryRepository $repository;
    private DocumentRepository $eventRepository;
    private LoggerInterface $logger;

    public function __construct(
        EventLocationHistoryRepository $eventLocationHistoryRepository,
        DocumentRepository $eventRepository,
        LoggerInterface $logger
    ) {
        $this->repository = $eventLocationHistoryRepository;
        $this->eventRepository = $eventRepository;
        $this->logger = $logger;
    }

    protected function applyLocationUpdated(LocationUpdated $event): void
    {
        try {
            $oldPlaceId = $this->getOldPlaceUuid($event->getItemId());
        } catch (DocumentDoesNotExist $e) {
            $this->logger->error(sprintf('Failed to store location updated: %s', $e->getMessage()));
            return;
        }

        $this->repository->storeEventLocationMove(
            new UUID($event->getItemId()),
            $oldPlaceId,
            new UUID($event->getLocationId()->toString())
        );
    }

    protected function applyEventCreated(EventCreated $event): void
    {
        $this->repository->storeEventLocationStartingPoint(
            new UUID($event->getEventId()),
            new UUID($event->getLocation()->toString())
        );
    }

    protected function applyEventCopied(EventCopied $event): void
    {
        try {
            $placeId = $this->getOldPlaceUuid($event->getOriginalEventId());
        } catch (DocumentDoesNotExist $e) {
            $this->logger->error(sprintf('Failed to store location updated: %s', $e->getMessage()));
            return;
        }

        $this->repository->storeEventLocationStartingPoint(
            new UUID($event->getItemId()),
            $placeId
        );
    }

    /**
     * @throws DocumentDoesNotExist
     */
    private function getOldPlaceUuid(string $eventId): UUID
    {
        $myEvent = $this->eventRepository->fetch($eventId);

        $body = $myEvent->getAssocBody();

        $id = (new PlaceIDParser())->fromUrl(new Url($body['location']['@id']));
        return new UUID($id->toString());
    }
}
