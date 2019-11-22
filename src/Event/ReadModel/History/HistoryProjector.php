<?php

namespace CultuurNet\UDB3\Event\ReadModel\History;

use Broadway\Domain\DomainMessage;
use CultuurNet\UDB3\Cdb\EventItemFactory;
use CultuurNet\UDB3\Event\EventEvent;
use CultuurNet\UDB3\Event\Events\DescriptionTranslated;
use CultuurNet\UDB3\Event\Events\EventCopied;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\EventImportedFromUDB2;
use CultuurNet\UDB3\Event\Events\EventUpdatedFromUDB2;
use CultuurNet\UDB3\Event\Events\LabelAdded;
use CultuurNet\UDB3\Event\Events\LabelRemoved;
use CultuurNet\UDB3\Event\Events\TitleTranslated;
use CultuurNet\UDB3\History\BaseHistoryProjector;
use CultuurNet\UDB3\History\Log;
use CultuurNet\UDB3\Offer\Events\AbstractEvent;
use DateTime;
use DateTimeZone;

final class HistoryProjector extends BaseHistoryProjector
{

    /**
     * @param DomainMessage $domainMessage
     * @uses applyEventImportedFromUDB2
     * @uses applyEventUpdatedFromUDB2
     */
    public function handle(DomainMessage $domainMessage)
    {
        $eventHandlers = [
            EventImportedFromUDB2::class => [$this, 'applyEventImportedFromUDB2'],
            EventUpdatedFromUDB2::class => [$this, 'applyEventUpdatedFromUDB2'],
            EventCreated::class => $this->createEventEventHandler(
                function () {
                    return 'Aangemaakt in UiTdatabank';
                }
            ),
            EventCopied::class => $this->createOfferEventHandler(
                function (EventCopied $eventCopied) {
                    return 'Event gekopieerd van ' . $eventCopied->getOriginalEventId();
                }
            ),
            LabelAdded::class => $this->createOfferEventHandler(
                function (LabelAdded $labelAdded) {
                    return "Label '{$labelAdded->getLabel()}' toegepast";
                }
            ),
            LabelRemoved::class => $this->createOfferEventHandler(
                function (LabelRemoved $labelRemoved) {
                    return "Label '{$labelRemoved->getLabel()}' verwijderd";
                }
            ),
            TitleTranslated::class => $this->createOfferEventHandler(
                function (TitleTranslated $titleTranslated) {
                    return "Titel vertaald ({$titleTranslated->getLanguage()})";
                }
            ),
            DescriptionTranslated::class => $this->createOfferEventHandler(
                function (DescriptionTranslated $descriptionTranslated) {
                    return "Beschrijving vertaald ({$descriptionTranslated->getLanguage()})";
                }
            ),
        ];

        $event = $domainMessage->getPayload();
        $eventName = get_class($event);
        if (isset($eventHandlers[$eventName])) {
            $eventHandlers[$eventName]($event, $domainMessage);
        }
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

    private function createEventEventHandler(callable $descriptionCallback): callable
    {
        return function (EventEvent $event, DomainMessage $domainMessage) use ($descriptionCallback) {
            $description = $descriptionCallback($event, $domainMessage);

            $this->writeHistory(
                $event->getEventId(),
                $this->createGenericLog($domainMessage, $description)
            );
        };
    }

    private function createOfferEventHandler(callable $descriptionCallback): callable
    {
        return function (AbstractEvent $event, DomainMessage $domainMessage) use ($descriptionCallback) {
            $description = $descriptionCallback($event, $domainMessage);

            $this->writeHistory(
                $event->getItemId(),
                $this->createGenericLog($domainMessage, $description)
            );
        };
    }

    private function udb2DateStringToNativeDate($dateString): DateTime
    {
        return DateTime::createFromFormat(
            'Y-m-d?H:i:s',
            $dateString,
            new DateTimeZone('Europe/Brussels')
        );
    }
}
