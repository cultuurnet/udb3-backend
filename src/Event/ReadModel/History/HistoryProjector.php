<?php

namespace CultuurNet\UDB3\Event\ReadModel\History;

use Broadway\Domain\DomainMessage;
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
use CultuurNet\UDB3\Offer\ReadModel\History\OfferHistoryProjector;
use ValueObjects\StringLiteral\StringLiteral;

class HistoryProjector extends OfferHistoryProjector implements EventListenerInterface
{

    protected function applyEventImportedFromUDB2(
        EventImportedFromUDB2 $eventImportedFromUDB2,
        DomainMessage $domainMessage
    ) {
        $udb2Event = EventItemFactory::createEventFromCdbXml(
            $eventImportedFromUDB2->getCdbXmlNamespaceUri(),
            $eventImportedFromUDB2->getCdbXml()
        );

        $this->writeHistory(
            $eventImportedFromUDB2->getEventId(),
            new Log(
                $this->dateFromUdb2DateString(
                    $udb2Event->getCreationDate()
                ),
                new StringLiteral('Aangemaakt in UDB2'),
                new StringLiteral($udb2Event->getCreatedBy())
            )
        );

        $this->writeHistory(
            $eventImportedFromUDB2->getEventId(),
            new Log(
                $this->domainMessageDateToNativeDate(
                    $domainMessage->getRecordedOn()
                ),
                new StringLiteral('Geïmporteerd vanuit UDB2'),
                null,
                $this->getApiKeyFromMetadata($domainMessage->getMetadata()),
                $this->getApiFromMetadata($domainMessage->getMetadata())
            )
        );
    }

    protected function applyEventUpdatedFromUDB2(
        EventUpdatedFromUDB2 $eventUpdatedFromUDB2,
        DomainMessage $domainMessage
    ) {
        $this->writeHistory(
            $eventUpdatedFromUDB2->getEventId(),
            new Log(
                $this->domainMessageDateToNativeDate($domainMessage->getRecordedOn()),
                new StringLiteral('Geüpdatet vanuit UDB2'),
                null,
                $this->getApiKeyFromMetadata($domainMessage->getMetadata()),
                $this->getApiFromMetadata($domainMessage->getMetadata())
            )
        );
    }

    /**
     * @param EventCreated $eventCreated
     * @param DomainMessage $domainMessage
     */
    protected function applyEventCreated(
        EventCreated $eventCreated,
        DomainMessage $domainMessage
    ) {
        $this->writeHistory(
            $eventCreated->getEventId(),
            new Log(
                $this->domainMessageDateToNativeDate(
                    $domainMessage->getRecordedOn()
                ),
                new StringLiteral('Aangemaakt in UiTdatabank'),
                $this->getAuthorFromMetadata($domainMessage->getMetadata()),
                $this->getApiKeyFromMetadata($domainMessage->getMetadata()),
                $this->getApiFromMetadata($domainMessage->getMetadata())
            )
        );
    }

    /**
     * @param EventCopied $eventCopied
     * @param DomainMessage $domainMessage
     */
    protected function applyEventCopied(
        EventCopied $eventCopied,
        DomainMessage $domainMessage
    ) {
        $this->writeHistory(
            $eventCopied->getItemId(),
            new Log(
                $this->domainMessageDateToNativeDate(
                    $domainMessage->getRecordedOn()
                ),
                new StringLiteral('Event gekopieerd van ' . $eventCopied->getOriginalEventId()),
                $this->getAuthorFromMetadata($domainMessage->getMetadata()),
                $this->getApiKeyFromMetadata($domainMessage->getMetadata()),
                $this->getApiFromMetadata($domainMessage->getMetadata())
            )
        );
    }

    /**
     * @return string
     */
    protected function getLabelAddedClassName()
    {
        return LabelAdded::class;
    }

    /**
     * @return string
     */
    protected function getLabelRemovedClassName()
    {
        return LabelRemoved::class;
    }

    /**
     * @return string
     */
    protected function getTitleTranslatedClassName()
    {
        return TitleTranslated::class;
    }

    /**
     * @return string
     */
    protected function getDescriptionTranslatedClassName()
    {
        return DescriptionTranslated::class;
    }
}
