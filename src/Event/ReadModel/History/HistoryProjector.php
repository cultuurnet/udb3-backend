<?php

namespace CultuurNet\UDB3\Event\ReadModel\History;

use Broadway\Domain\DomainMessage;
use CultuurNet\UDB3\Cdb\EventItemFactory;
use CultuurNet\UDB3\Event\Events\AudienceUpdated;
use CultuurNet\UDB3\Event\Events\BookingInfoUpdated;
use CultuurNet\UDB3\Event\Events\CalendarUpdated;
use CultuurNet\UDB3\Event\Events\DescriptionTranslated;
use CultuurNet\UDB3\Event\Events\EventCopied;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\EventImportedFromUDB2;
use CultuurNet\UDB3\Event\Events\EventUpdatedFromUDB2;
use CultuurNet\UDB3\Event\Events\LabelAdded;
use CultuurNet\UDB3\Event\Events\LabelRemoved;
use CultuurNet\UDB3\Event\Events\Moderation\Approved;
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
            case $event instanceof Approved:
                $this->projectApproved($domainMessage);
                break;
            case $event instanceof AudienceUpdated:
                $this->projectAudienceUpdated($domainMessage);
                break;
            case $event instanceof BookingInfoUpdated:
                $this->projectBookingInfoUpdated($domainMessage);
                break;
            case $event instanceof CalendarUpdated:
                $this->projectCalendarUpdated($domainMessage);
                break;
            case $event instanceof EventImportedFromUDB2:
                $this->projectEventImportedFromUDB2($domainMessage);
                break;
            case $event instanceof EventUpdatedFromUDB2:
                $this->projectEventUpdatedFromUDB2($domainMessage);
                break;
            case $event instanceof EventCreated:
                $this->projectEventCreated($domainMessage);
                break;
            case $event instanceof EventCopied:
                $this->projectEventCopied($domainMessage);
                break;
            case $event instanceof LabelAdded:
                $this->projectLabelAdded($domainMessage);
                break;
            case $event instanceof LabelRemoved:
                $this->projectLabelRemoved($domainMessage);
                break;
            case $event instanceof DescriptionTranslated:
                $this->projectDescriptionTranslated($domainMessage);
                break;
            case $event instanceof TitleTranslated:
                $this->projectTitleTranslated($domainMessage);
                break;
        }
    }

    private function projectAudienceUpdated(DomainMessage $domainMessage): void
    {
        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, 'Toegang aangepast')
        );
    }

    private function projectEventImportedFromUDB2(DomainMessage $domainMessage): void
    {
        $event = $domainMessage->getPayload();
        $udb2Event = EventItemFactory::createEventFromCdbXml(
            $event->getCdbXmlNamespaceUri(),
            $event->getCdbXml()
        );

        $udb2Log = Log::createFromDomainMessage($domainMessage, 'Aangemaakt in UDB2');

        if ($udb2Event->getCreatedBy()) {
            $udb2Log = $udb2Log->withAuthor($udb2Event->getCreatedBy());
        }

        $udb2Date = DateTime::createFromFormat(
            'Y-m-d?H:i:s',
            $udb2Event->getCreationDate(),
            new DateTimeZone('Europe/Brussels')
        );
        if ($udb2Date) {
            $udb2Log = $udb2Log->withDate($udb2Date);
        }

        $this->writeHistory($domainMessage->getId(), $udb2Log);

        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, 'Geïmporteerd vanuit UDB2')
                ->withoutAuthor()
        );
    }

    private function projectEventUpdatedFromUDB2(DomainMessage $domainMessage): void
    {
        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, 'Geüpdatet vanuit UDB2')
                ->withoutAuthor()
        );
    }

    private function projectEventCreated(DomainMessage $domainMessage): void
    {
        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, 'Aangemaakt in UiTdatabank')
        );
    }

    private function projectEventCopied(DomainMessage $domainMessage): void
    {
        $eventCopied = $domainMessage->getPayload();

        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, 'Event gekopieerd van ' . $eventCopied->getOriginalEventId())
        );
    }
}
