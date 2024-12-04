<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\History;

use Broadway\EventHandling\EventListener;
use CultureFeed_Cdb_ParseException;
use CultuurNet\UDB3\Cdb\CdbId\EventCdbIdExtractorInterface;
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
    private EventCdbIdExtractorInterface $cdbIdExtractor;
    private LoggerInterface $logger;

    public function __construct(
        EventPlaceHistoryRepository $eventLocationHistoryRepository,
        DocumentRepository $eventRepository,
        EventCdbIdExtractorInterface $cdbIdExtractor,
        LoggerInterface $logger
    ) {
        $this->repository = $eventLocationHistoryRepository;
        $this->eventRepository = $eventRepository;
        $this->cdbIdExtractor = $cdbIdExtractor;
        $this->logger = $logger;
    }

    protected function applyLocationUpdated(LocationUpdated $locationUpdated): void
    {
        try {
            $oldPlaceId = $this->getOldPlaceUuid($locationUpdated->getItemId());
        } catch (DocumentDoesNotExist $e) {
            $this->logger->error(sprintf('Failed to store location updated: %s', $e->getMessage()));
            return;
        }

        $this->repository->storeEventPlaceMove(
            new UUID($locationUpdated->getItemId()),
            $oldPlaceId,
            new UUID($locationUpdated->getLocationId()->toString())
        );
    }

    protected function applyEventCreated(EventCreated $eventCreated): void
    {
        $this->repository->storeEventPlaceStartingPoint(
            new UUID($eventCreated->getEventId()),
            new UUID($eventCreated->getLocation()->toString())
        );
    }

    protected function applyEventCopied(EventCopied $eventCopied): void
    {
        try {
            $placeId = $this->getOldPlaceUuid($eventCopied->getOriginalEventId());
        } catch (DocumentDoesNotExist $e) {
            $this->logger->error(sprintf('Failed to store event copied: %s', $e->getMessage()));
            return;
        }

        $this->repository->storeEventPlaceStartingPoint(
            new UUID($eventCopied->getItemId()),
            $placeId
        );
    }

    protected function applyEventImportedFromUDB2(EventImportedFromUDB2 $eventImportedFromUDB2): void
    {
        try {
            $udb2Event = EventItemFactory::createEventFromCdbXml(
                $eventImportedFromUDB2->getCdbXmlNamespaceUri(),
                $eventImportedFromUDB2->getCdbXml()
            );
        } catch (CultureFeed_Cdb_ParseException $e) {
            $this->logger->error(sprintf('Failed to store event imported from UDB2: %s', $e->getMessage()));
            return;
        }

        $newPlaceId = $this->cdbIdExtractor->getRelatedPlaceCdbId($udb2Event);

        if ($newPlaceId === null) {
            return;
        }

        $this->repository->storeEventPlaceStartingPoint(
            new UUID($eventImportedFromUDB2->getEventId()),
            new UUID($newPlaceId)
        );
    }

    protected function applyEventUpdatedFromUDB2(EventUpdatedFromUDB2 $eventUpdatedFromUDB2): void
    {
        try {
            $udb2Event = EventItemFactory::createEventFromCdbXml(
                $eventUpdatedFromUDB2->getCdbXmlNamespaceUri(),
                $eventUpdatedFromUDB2->getCdbXml()
            );
        } catch (CultureFeed_Cdb_ParseException $e) {
            $this->logger->error(sprintf('Failed to store event updated from UDB2, could not read XML: %s', $e->getMessage()));
            return;
        }

        try {
            $oldPlaceId = $this->getOldPlaceUuid($eventUpdatedFromUDB2->getEventId());
        } catch (DocumentDoesNotExist $e) {
            $this->logger->error(sprintf('Failed to store event updated from UDB2: %s', $e->getMessage()));
            return;
        }

        $newPlaceId = $this->cdbIdExtractor->getRelatedPlaceCdbId($udb2Event);

        if ($newPlaceId === null || $newPlaceId === $oldPlaceId->toString()) {
            return;
        }

        $this->repository->storeEventPlaceMove(
            new UUID($eventUpdatedFromUDB2->getEventId()),
            $oldPlaceId,
            new UUID($newPlaceId)
        );
    }

    protected function applyMajorInfoUpdated(MajorInfoUpdated $majorInfoUpdated): void
    {
        try {
            $oldPlaceId = $this->getOldPlaceUuid($majorInfoUpdated->getItemId());
        } catch (DocumentDoesNotExist $e) {
            $this->logger->error(sprintf('Failed to store location updated: %s', $e->getMessage()));
            return;
        }

        $newPlaceId = new UUID($majorInfoUpdated->getLocation()->toString());

        if ($newPlaceId->sameAs($oldPlaceId)) {
            return;
        }

        $this->repository->storeEventPlaceMove(
            new UUID($majorInfoUpdated->getItemId()),
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
