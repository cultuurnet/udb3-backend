<?php

namespace CultuurNet\UDB3\Event\ReadModel\History;

use Broadway\Domain\DateTime as BroadwayDateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventListenerInterface;
use CultuurNet\UDB3\Cdb\EventItemFactory;
use CultuurNet\UDB3\Event\Events\DescriptionTranslated;
use CultuurNet\UDB3\Event\Events\EventCopied;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\EventImportedFromUDB2;
use CultuurNet\UDB3\Event\Events\EventUpdatedFromUDB2;
use CultuurNet\UDB3\Event\Events\LabelAdded;
use CultuurNet\UDB3\Event\Events\LabelRemoved;
use CultuurNet\UDB3\Event\Events\TitleTranslated;
use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use DateTime;
use DateTimeZone;

final class HistoryProjector implements EventListenerInterface
{
    use DelegateEventHandlingToSpecificMethodTrait;

    /**
     * @var DocumentRepositoryInterface
     */
    private $documentRepository;

    public function __construct(DocumentRepositoryInterface $documentRepository)
    {
        $this->documentRepository = $documentRepository;
    }

    private function applyEventImportedFromUDB2(
        EventImportedFromUDB2 $eventImportedFromUDB2,
        DomainMessage $domainMessage
    ): void {
        $udb2Event = EventItemFactory::createEventFromCdbXml(
            $eventImportedFromUDB2->getCdbXmlNamespaceUri(),
            $eventImportedFromUDB2->getCdbXml()
        );

        $this->writeHistory(
            $eventImportedFromUDB2->getEventId(),
            new Log(
                $this->udb2DateStringToNativeDate(
                    $udb2Event->getCreationDate()
                ),
                'Aangemaakt in UDB2',
                $udb2Event->getCreatedBy()
            )
        );

        $this->writeHistory(
            $eventImportedFromUDB2->getEventId(),
            new Log(
                $this->domainMessageDateToNativeDate(
                    $domainMessage->getRecordedOn()
                ),
                'Geïmporteerd vanuit UDB2',
                null,
                $this->getApiKeyFromMetadata($domainMessage->getMetadata()),
                $this->getApiFromMetadata($domainMessage->getMetadata()),
                $this->getConsumerFromMetadata($domainMessage->getMetadata())
            )
        );
    }

    private function applyEventUpdatedFromUDB2(
        EventUpdatedFromUDB2 $eventUpdatedFromUDB2,
        DomainMessage $domainMessage
    ): void {
        $this->writeHistory(
            $eventUpdatedFromUDB2->getEventId(),
            new Log(
                $this->domainMessageDateToNativeDate($domainMessage->getRecordedOn()),
                'Geüpdatet vanuit UDB2',
                null,
                $this->getApiKeyFromMetadata($domainMessage->getMetadata()),
                $this->getApiFromMetadata($domainMessage->getMetadata()),
                $this->getConsumerFromMetadata($domainMessage->getMetadata())
            )
        );
    }

    private function applyEventCreated(
        EventCreated $eventCreated,
        DomainMessage $domainMessage
    ): void {
        $this->writeHistory(
            $eventCreated->getEventId(),
            $this->createGenericLog($domainMessage, 'Aangemaakt in UiTdatabank')
        );
    }

    private function applyEventCopied(
        EventCopied $eventCopied,
        DomainMessage $domainMessage
    ): void {
        $this->writeHistory(
            $eventCopied->getItemId(),
            $this->createGenericLog($domainMessage, 'Event gekopieerd van ' . $eventCopied->getOriginalEventId())
        );
    }

    private function applyLabelAdded(
        LabelAdded $labelAdded,
        DomainMessage $domainMessage
    ): void {
        $this->writeHistory(
            $labelAdded->getItemId(),
            $this->createGenericLog($domainMessage, "Label '{$labelAdded->getLabel()}' toegepast")
        );
    }

    private function applyLabelRemoved(
        LabelRemoved $labelRemoved,
        DomainMessage $domainMessage
    ): void {
        $this->writeHistory(
            $labelRemoved->getItemId(),
            $this->createGenericLog($domainMessage, "Label '{$labelRemoved->getLabel()}' verwijderd")
        );
    }

    private function applyTitleTranslated(
        TitleTranslated $titleTranslated,
        DomainMessage $domainMessage
    ): void {
        $this->writeHistory(
            $titleTranslated->getItemId(),
            $this->createGenericLog($domainMessage, "Titel vertaald ({$titleTranslated->getLanguage()})")
        );
    }

    private function applyDescriptionTranslated(
        DescriptionTranslated $descriptionTranslated,
        DomainMessage $domainMessage
    ): void {
        $this->writeHistory(
            $descriptionTranslated->getItemId(),
            $this->createGenericLog($domainMessage, "Beschrijving vertaald ({$descriptionTranslated->getLanguage()})")
        );
    }

    private function createGenericLog(DomainMessage $domainMessage, string $description): Log
    {
        return new Log(
            $this->domainMessageDateToNativeDate($domainMessage->getRecordedOn()),
            $description,
            $this->getAuthorFromMetadata($domainMessage->getMetadata()),
            $this->getApiKeyFromMetadata($domainMessage->getMetadata()),
            $this->getApiFromMetadata($domainMessage->getMetadata()),
            $this->getConsumerFromMetadata($domainMessage->getMetadata())
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

    private function udb2DateStringToNativeDate($dateString): DateTime
    {
        return DateTime::createFromFormat(
            'Y-m-d?H:i:s',
            $dateString,
            new DateTimeZone('Europe/Brussels')
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
