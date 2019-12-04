<?php declare(strict_types=1);

namespace CultuurNet\UDB3\History;

use Broadway\Domain\DateTime as BroadwayDateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventListenerInterface;
use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use DateTime;

abstract class BaseHistoryProjector implements EventListenerInterface
{
    /**
     * @var DocumentRepositoryInterface
     */
    private $documentRepository;

    public function __construct(DocumentRepositoryInterface $documentRepository)
    {
        $this->documentRepository = $documentRepository;
    }

    protected function getConsumerFromMetadata(Metadata $metadata): ?string
    {
        $properties = $metadata->serialize();

        if (isset($properties['consumer']['name'])) {
            return (string) $properties['consumer']['name'];
        }

        return null;
    }

    protected function domainMessageDateToNativeDate(BroadwayDateTime $date): DateTime
    {
        $dateString = $date->toString();
        return DateTime::createFromFormat(
            BroadwayDateTime::FORMAT_STRING,
            $dateString
        );
    }

    protected function getApiFromMetadata(Metadata $metadata): ?string
    {
        $properties = $metadata->serialize();

        if (isset($properties['api'])) {
            return $properties['api'];
        }

        return null;
    }

    protected function getApiKeyFromMetadata(Metadata $metadata): ?string
    {
        $properties = $metadata->serialize();

        if (isset($properties['auth_api_key'])) {
            return $properties['auth_api_key'];
        }

        return null;
    }

    protected function getAuthorFromMetadata(Metadata $metadata): ?string
    {
        $properties = $metadata->serialize();

        if (isset($properties['user_nick'])) {
            return (string) $properties['user_nick'];
        }

        return null;
    }

    protected function loadDocumentFromRepositoryByEventId(string $eventId): JsonDocument
    {
        $historyDocument = $this->documentRepository->get($eventId);

        if (!$historyDocument) {
            $historyDocument = new JsonDocument($eventId, '[]');
        }

        return $historyDocument;
    }

    protected function writeHistory(string $eventId, Log $log): void
    {
        $historyDocument = $this->loadDocumentFromRepositoryByEventId($eventId);

        $history = $historyDocument->getBody();

        // Append most recent one to the top.
        array_unshift($history, $log);

        $this->documentRepository->save(
            $historyDocument->withBody($history)
        );
    }

    protected function createGenericLog(DomainMessage $domainMessage, string $description): Log
    {
        return new Log(
            $domainMessage->getId(),
            $this->domainMessageDateToNativeDate($domainMessage->getRecordedOn()),
            $description,
            $this->getAuthorFromMetadata($domainMessage->getMetadata()),
            $this->getApiKeyFromMetadata($domainMessage->getMetadata()),
            $this->getApiFromMetadata($domainMessage->getMetadata()),
            $this->getConsumerFromMetadata($domainMessage->getMetadata())
        );
    }
}
