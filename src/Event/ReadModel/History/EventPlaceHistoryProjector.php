<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\History;

use Broadway\EventHandling\EventListener;
use CultureFeed_Cdb_ParseException;
use CultuurNet\UDB3\Cdb\EventItemFactory;
use CultuurNet\UDB3\Event\Events\EventCopied;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\EventImportedFromUDB2;
use CultuurNet\UDB3\Event\Events\EventUpdatedFromUDB2;
use CultuurNet\UDB3\Event\Events\LocationUpdated;
use CultuurNet\UDB3\Event\Events\MajorInfoUpdated;
use CultuurNet\UDB3\Event\ReadModel\Relations\EventPlaceHistoryRepository;
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

    private EventPlaceHistoryRepository $repository;
    private DocumentRepository $eventRepository;
    private LoggerInterface $logger;

    public function __construct(
        EventPlaceHistoryRepository $eventLocationHistoryRepository,
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

        $this->repository->storeEventPlaceMove(
            new UUID($event->getItemId()),
            $oldPlaceId,
            new UUID($event->getLocationId()->toString())
        );
    }

    protected function applyEventCreated(EventCreated $event): void
    {
        $this->repository->storeEventPlaceStartingPoint(
            new UUID($event->getEventId()),
            new UUID($event->getLocation()->toString())
        );
    }

    protected function applyEventCopied(EventCopied $event): void
    {
        try {
            $placeId = $this->getOldPlaceUuid($event->getOriginalEventId());
        } catch (DocumentDoesNotExist $e) {
            $this->logger->error(sprintf('Failed to store event copied: %s', $e->getMessage()));
            return;
        }

        $this->repository->storeEventPlaceStartingPoint(
            new UUID($event->getItemId()),
            $placeId
        );
    }

    protected function applyEventImportedFromUDB2(EventImportedFromUDB2 $event): void
    {
        try {
            $udb2Event = EventItemFactory::createEventFromCdbXml(
                $event->getCdbXmlNamespaceUri(),
                $event->getCdbXml()
            );
        } catch (CultureFeed_Cdb_ParseException $e) {
            $this->logger->error(sprintf('Failed to store event imported from UDB2: %s', $e->getMessage()));
            return;
        }

        $this->repository->storeEventPlaceStartingPoint(
            new UUID($event->getEventId()),
            new UUID($udb2Event->getLocation()->getCdbid())
        );
    }

    protected function applyEventUpdatedFromUDB2(EventUpdatedFromUDB2 $event): void
    {
        try {
            $udb2Event = EventItemFactory::createEventFromCdbXml(
                $event->getCdbXmlNamespaceUri(),
                $event->getCdbXml()
            );
        } catch (CultureFeed_Cdb_ParseException $e) {
            $this->logger->error(sprintf('Failed to store event updated from UDB2, could not read XML: %s', $e->getMessage()));
            return;
        }

        try {
            $oldPlaceId = $this->getOldPlaceUuid($event->getEventId());
        } catch (DocumentDoesNotExist $e) {
            $this->logger->error(sprintf('Failed to store event updated from UDB2: %s', $e->getMessage()));
            return;
        }

        $newPlaceId = new UUID($udb2Event->getLocation()->getCdbid());

        if ($newPlaceId->sameAs($oldPlaceId)) {
            return;
        }

        $this->repository->storeEventPlaceMove(
            new UUID($event->getEventId()),
            $oldPlaceId,
            $newPlaceId
        );
    }

    protected function applyMajorInfoUpdated(MajorInfoUpdated $event): void
    {
        try {
            $oldPlaceId = $this->getOldPlaceUuid($event->getItemId());
        } catch (DocumentDoesNotExist $e) {
            $this->logger->error(sprintf('Failed to store location updated: %s', $e->getMessage()));
            return;
        }

        $newPlaceId = new UUID($event->getLocation()->toString());

        if ($newPlaceId->sameAs($oldPlaceId)) {
            return;
        }

        $this->repository->storeEventPlaceMove(
            new UUID($event->getItemId()),
            $oldPlaceId,
            $newPlaceId
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
