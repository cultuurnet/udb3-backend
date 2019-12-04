<?php

namespace CultuurNet\UDB3\Event\ReadModel\History;

use Broadway\Domain\DomainMessage;
use CultuurNet\UDB3\Cdb\EventItemFactory;
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
use CultuurNet\UDB3\Offer\ReadModel\History\OfferHistoryProjectorTrait;
use DateTime;
use DateTimeZone;

final class HistoryProjector extends BaseHistoryProjector
{
    use OfferHistoryProjectorTrait;

    public function handle(DomainMessage $domainMessage): void
    {
        $event = $domainMessage->getPayload();

        switch (true) {
            case $event instanceof EventImportedFromUDB2:
                $this->projectEventImportedFromUDB2($event, $domainMessage);
                break;
            case $event instanceof EventUpdatedFromUDB2:
                $this->projectEventUpdatedFromUDB2($event, $domainMessage);
                break;
            case $event instanceof EventCreated:
                $this->projectEventCreated($event, $domainMessage);
                break;
            case $event instanceof EventCopied:
                $this->projectEventCopied($event, $domainMessage);
                break;
            case $event instanceof LabelAdded:
                $this->projectLabelAdded($event, $domainMessage);
                break;
            case $event instanceof LabelRemoved:
                $this->projectLabelRemoved($event, $domainMessage);
                break;
            case $event instanceof DescriptionTranslated:
                $this->projectDescriptionTranslated($event, $domainMessage);
                break;
            case $event instanceof TitleTranslated:
                $this->projectTitleTranslated($event, $domainMessage);
                break;
        }
    }

    private function projectEventImportedFromUDB2(
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
                DateTime::createFromFormat(
                    'Y-m-d?H:i:s',
                    $udb2Event->getCreationDate(),
                    new DateTimeZone('Europe/Brussels')
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

    private function projectEventUpdatedFromUDB2(
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

    private function projectEventCreated(EventCreated $eventCreated, DomainMessage $domainMessage): void
    {
        $this->writeHistory(
            $eventCreated->getEventId(),
            $this->createGenericLog($domainMessage, 'Aangemaakt in UiTdatabank')
        );
    }

    private function projectEventCopied(EventCopied $eventCopied, DomainMessage $domainMessage): void
    {
        $this->writeHistory(
            $eventCopied->getItemId(),
            $this->createGenericLog($domainMessage, 'Event gekopieerd van ' . $eventCopied->getOriginalEventId())
        );
    }
}
