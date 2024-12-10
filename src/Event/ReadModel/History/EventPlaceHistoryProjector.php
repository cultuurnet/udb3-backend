<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\History;

use Broadway\Domain\DomainMessage;
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
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
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

    protected function applyLocationUpdated(LocationUpdated $locationUpdated, DomainMessage $domainMessage): void
    {
        try {
            $oldPlaceId = $this->getOldPlaceUuid($locationUpdated->getItemId());
        } catch (DocumentDoesNotExist $e) {
            $this->logger->error(sprintf('Failed to store location updated: %s', $e->getMessage()));
            return;
        }

        $this->repository->storeEventPlaceMove(
            new Uuid($locationUpdated->getItemId()),
            $oldPlaceId,
            new Uuid($locationUpdated->getLocationId()->toString()),
            $domainMessage->getRecordedOn()->toNative()
        );
    }

    protected function applyEventCreated(EventCreated $eventCreated, DomainMessage $domainMessage): void
    {
        $this->repository->storeEventPlaceStartingPoint(
            new Uuid($eventCreated->getEventId()),
            new Uuid($eventCreated->getLocation()->toString()),
            $domainMessage->getRecordedOn()->toNative()
        );
    }

    protected function applyEventCopied(EventCopied $eventCopied, DomainMessage $domainMessage): void
    {
        try {
            $placeId = $this->getOldPlaceUuid($eventCopied->getOriginalEventId());
        } catch (DocumentDoesNotExist $e) {
            $this->logger->error(sprintf('Failed to store event copied: %s', $e->getMessage()));
            return;
        }

        $this->repository->storeEventPlaceStartingPoint(
            new Uuid($eventCopied->getItemId()),
            $placeId,
            $domainMessage->getRecordedOn()->toNative()
        );
    }

    protected function applyEventImportedFromUDB2(EventImportedFromUDB2 $eventImportedFromUDB2, DomainMessage $domainMessage): void
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
            new Uuid($eventImportedFromUDB2->getEventId()),
            new Uuid($newPlaceId),
            $domainMessage->getRecordedOn()->toNative()
        );
    }

    protected function applyEventUpdatedFromUDB2(EventUpdatedFromUDB2 $eventUpdatedFromUDB2, DomainMessage $domainMessage): void
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
            new Uuid($eventUpdatedFromUDB2->getEventId()),
            $oldPlaceId,
            new Uuid($newPlaceId),
            $domainMessage->getRecordedOn()->toNative()
        );
    }

    protected function applyMajorInfoUpdated(MajorInfoUpdated $majorInfoUpdated, DomainMessage $domainMessage): void
    {
        try {
            $oldPlaceId = $this->getOldPlaceUuid($majorInfoUpdated->getItemId());
        } catch (DocumentDoesNotExist $e) {
            $this->logger->error(sprintf('Failed to store location updated: %s', $e->getMessage()));
            return;
        }

        $newPlaceId = new Uuid($majorInfoUpdated->getLocation()->toString());

        if ($newPlaceId->sameAs($oldPlaceId)) {
            return;
        }

        $this->repository->storeEventPlaceMove(
            new Uuid($majorInfoUpdated->getItemId()),
            $oldPlaceId,
            $newPlaceId,
            $domainMessage->getRecordedOn()->toNative()
        );
    }

    /**
     * @throws DocumentDoesNotExist
     */
    private function getOldPlaceUuid(string $eventId): Uuid
    {
        $myEvent = $this->eventRepository->fetch($eventId);

        $body = $myEvent->getAssocBody();

        $id = (new PlaceIDParser())->fromUrl(new Url($body['location']['@id']));
        return new Uuid($id->toString());
    }
}
