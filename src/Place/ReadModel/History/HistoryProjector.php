<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\History;

use Broadway\Domain\DateTime as BroadwayDateTime;
use Broadway\Domain\DomainMessage;

use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventListenerInterface;
use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
use CultuurNet\UDB3\Event\ReadModel\History\Log;
use CultuurNet\UDB3\Place\Events\LabelAdded;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Place\ReadModel\Enum\EventDescription;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use DateTime;
use function GuzzleHttp\Promise\inspect;

final class HistoryProjector implements EventListenerInterface
{
    /**
     * @var DocumentRepositoryInterface
     */
    private $documentRepository;

    public function __construct(DocumentRepositoryInterface $documentRepository)
    {
        $this->documentRepository = $documentRepository;
    }

    public function handle(DomainMessage $domainMessage)
    {
        $event = $domainMessage->getPayload();
        switch (true) {
            case $event instanceof PlaceCreated:
                $this->handlePlaceCreated($event, $domainMessage);
                break;
            case $event instanceof LabelAdded:
                $this->handleLabelAdded($event, $domainMessage);
                break;
        }
    }

    private function handlePlaceCreated(PlaceCreated $event, DomainMessage $domainMessage): void
    {
        $this->writeHistory(
            $event->getPlaceId(),
            new Log(
                $this->domainMessageDateToNativeDate($domainMessage->getRecordedOn()),
                EventDescription::CREATED,
                $this->getAuthorFromMetadata($domainMessage->getMetadata()),
                $this->getApiKeyFromMetadata($domainMessage->getMetadata()),
                $this->getApiFromMetadata($domainMessage->getMetadata()),
                $this->getConsumerFromMetadata($domainMessage->getMetadata())
            )
        );
    }

    private function handleLabelAdded(LabelAdded $event, DomainMessage $domainMessage): void
    {
        $this->writeHistory(
            $event->getItemId(),
            new Log(
                $this->domainMessageDateToNativeDate($domainMessage->getRecordedOn()),
                EventDescription::LABEL_ADDED,
                $this->getAuthorFromMetadata($domainMessage->getMetadata()),
                $this->getApiKeyFromMetadata($domainMessage->getMetadata()),
                $this->getApiFromMetadata($domainMessage->getMetadata()),
                $this->getConsumerFromMetadata($domainMessage->getMetadata())
            )
        );
    }

    private function domainMessageDateToNativeDate(BroadwayDateTime $date): DateTime
    {
        $dateString = $date->toString();
        return DateTime::createFromFormat(
            BroadwayDateTime::FORMAT_STRING,
            $dateString
        );
    }

    private function writeHistory(string $eventId, Log $log): void
    {
        $historyDocument = $this->loadDocumentFromRepositoryByEventId($eventId);

        $history = $historyDocument->getBody();

        // Append most recent one to the top.
        array_unshift($history, $log);

        $this->documentRepository->save(
            $historyDocument->withBody($history)
        );
    }

    private function loadDocumentFromRepositoryByEventId(string $eventId): JsonDocument
    {
        $historyDocument = $this->documentRepository->get($eventId);

        if (!$historyDocument) {
            $historyDocument = new JsonDocument($eventId, '[]');
        }

        return $historyDocument;
    }

    private function getAuthorFromMetadata(Metadata $metadata): ?string
    {
        $properties = $metadata->serialize();

        if (isset($properties['user_nick'])) {
            return (string) $properties['user_nick'];
        }

        return null;
    }

    private function getConsumerFromMetadata(Metadata $metadata): ?string
    {
        $properties = $metadata->serialize();

        if (isset($properties['consumer']['name'])) {
            return (string) $properties['consumer']['name'];
        }

        return null;
    }

    private function getApiKeyFromMetadata(Metadata $metadata): ?string
    {
        $properties = $metadata->serialize();

        if (isset($properties['auth_api_key'])) {
            return $properties['auth_api_key'];
        }

        return null;
    }

    private function getApiFromMetadata(Metadata $metadata): ?string
    {
        $properties = $metadata->serialize();

        if (isset($properties['api'])) {
            return $properties['api'];
        }

        return null;
    }
}
