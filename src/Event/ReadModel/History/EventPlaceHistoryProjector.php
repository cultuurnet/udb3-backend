<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\History;

use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Event\Events\EventCopied;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\LocationUpdated;
use CultuurNet\UDB3\Event\ReadModel\Relations\EventLocationHistoryRepository;
use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
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
            $this->repository->storeEventLocationMove(
                new UUID($event->getItemId()),
                $this->getOldPlaceUuid($event->getItemId()),
                new UUID($event->getLocationId()->toString())
            );
        } catch (DocumentDoesNotExist $e) {
            $this->logger->error(sprintf('Failed to store location updated: %s', $e->getMessage()));
        }
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
            $this->repository->storeEventLocationStartingPoint(
                new UUID($event->getItemId()),
                $this->getOldPlaceUuid($event->getOriginalEventId())
            );
        } catch (DocumentDoesNotExist $e) {
            $this->logger->error(sprintf('Failed to store location updated: %s', $e->getMessage()));
        }
    }

    /**
     * @throws DocumentDoesNotExist
     */
    private function getOldPlaceUuid(string $eventId): UUID
    {
        $myEvent = $this->eventRepository->fetch($eventId);

        $body = $myEvent->getAssocBody();

        $url = $body['location']['@id'];

        // Return everything after the last slash
        // https://io.uitdatabank.be/event/fd7dbfaf-5162-4181-9446-e61c4f5ef3f2 -> fd7dbfaf-5162-4181-9446-e61c4f5ef3f2
        return new UUID(substr($url, strrpos($url, '/') + 1));
    }
}
