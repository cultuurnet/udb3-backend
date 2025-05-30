<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\History;

use Broadway\Domain\DomainMessage;
use CultuurNet\UDB3\Cdb\EventItemFactory;
use CultuurNet\UDB3\Event\Events\AttendanceModeUpdated;
use CultuurNet\UDB3\Event\Events\AudienceUpdated;
use CultuurNet\UDB3\Event\Events\AvailableFromUpdated;
use CultuurNet\UDB3\Event\Events\BookingInfoUpdated;
use CultuurNet\UDB3\Event\Events\CalendarUpdated;
use CultuurNet\UDB3\Event\Events\ContactPointUpdated;
use CultuurNet\UDB3\Event\Events\DescriptionTranslated;
use CultuurNet\UDB3\Event\Events\DescriptionUpdated;
use CultuurNet\UDB3\Event\Events\EventCopied;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\EventDeleted;
use CultuurNet\UDB3\Event\Events\EventImportedFromUDB2;
use CultuurNet\UDB3\Event\Events\EventUpdatedFromUDB2;
use CultuurNet\UDB3\Event\Events\FacilitiesUpdated;
use CultuurNet\UDB3\Event\Events\GeoCoordinatesUpdated;
use CultuurNet\UDB3\Event\Events\Image\ImagesImportedFromUDB2;
use CultuurNet\UDB3\Event\Events\Image\ImagesUpdatedFromUDB2;
use CultuurNet\UDB3\Event\Events\ImageAdded;
use CultuurNet\UDB3\Event\Events\ImageRemoved;
use CultuurNet\UDB3\Event\Events\ImageUpdated;
use CultuurNet\UDB3\Event\Events\LabelAdded;
use CultuurNet\UDB3\Event\Events\LabelRemoved;
use CultuurNet\UDB3\Event\Events\LabelsImported;
use CultuurNet\UDB3\Event\Events\LabelsReplaced as LabelsReplacedEvent;
use CultuurNet\UDB3\Place\Events\LabelsReplaced as LabelsReplacedPlace;
use CultuurNet\UDB3\Event\Events\LocationUpdated;
use CultuurNet\UDB3\Event\Events\MainImageSelected;
use CultuurNet\UDB3\Event\Events\MajorInfoUpdated;
use CultuurNet\UDB3\Event\Events\Moderation\Approved;
use CultuurNet\UDB3\Event\Events\Moderation\FlaggedAsDuplicate;
use CultuurNet\UDB3\Event\Events\Moderation\FlaggedAsInappropriate;
use CultuurNet\UDB3\Event\Events\Moderation\Published;
use CultuurNet\UDB3\Event\Events\Moderation\Rejected;
use CultuurNet\UDB3\Event\Events\OnlineUrlDeleted;
use CultuurNet\UDB3\Event\Events\OnlineUrlUpdated;
use CultuurNet\UDB3\Event\Events\OrganizerDeleted;
use CultuurNet\UDB3\Event\Events\OrganizerUpdated;
use CultuurNet\UDB3\Event\Events\PriceInfoUpdated;
use CultuurNet\UDB3\Event\Events\ThemeRemoved;
use CultuurNet\UDB3\Event\Events\ThemeUpdated;
use CultuurNet\UDB3\Event\Events\TitleTranslated;
use CultuurNet\UDB3\Event\Events\TitleUpdated;
use CultuurNet\UDB3\Event\Events\TypeUpdated;
use CultuurNet\UDB3\Event\Events\TypicalAgeRangeDeleted;
use CultuurNet\UDB3\Event\Events\TypicalAgeRangeUpdated;
use CultuurNet\UDB3\Event\Events\VideoAdded;
use CultuurNet\UDB3\Event\Events\VideoDeleted;
use CultuurNet\UDB3\Event\Events\VideoUpdated;
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
            case $event instanceof AttendanceModeUpdated:
                $this->projectAttendanceModeUpdated($domainMessage);
                break;
            case $event instanceof OnlineUrlUpdated:
                $this->projectOnlineUrlUpdated($domainMessage);
                break;
            case $event instanceof OnlineUrlDeleted:
                $this->projectOnlineUrlDeleted($domainMessage);
                break;
            case $event instanceof BookingInfoUpdated:
                $this->projectBookingInfoUpdated($domainMessage);
                break;
            case $event instanceof CalendarUpdated:
                $this->projectCalendarUpdated($domainMessage);
                break;
            case $event instanceof ContactPointUpdated:
                $this->projectContactPointUpdated($domainMessage);
                break;
            case $event instanceof DescriptionTranslated:
                $this->projectDescriptionTranslated($domainMessage);
                break;
            case $event instanceof DescriptionUpdated:
                $this->projectDescriptionUpdated($domainMessage);
                break;
            case $event instanceof EventCopied:
                $this->projectEventCopied($domainMessage);
                break;
            case $event instanceof EventCreated:
                $this->projectEventCreated($domainMessage);
                break;
            case $event instanceof EventDeleted:
                $this->projectEventDeleted($domainMessage);
                break;
            case $event instanceof EventImportedFromUDB2:
                $this->projectEventImportedFromUDB2($domainMessage);
                break;
            case $event instanceof EventUpdatedFromUDB2:
                $this->projectEventUpdatedFromUDB2($domainMessage);
                break;
            case $event instanceof FacilitiesUpdated:
                $this->projectFacilitiesUpdated($domainMessage);
                break;
            case $event instanceof FlaggedAsDuplicate:
                $this->projectFlaggedAsDuplicate($domainMessage);
                break;
            case $event instanceof FlaggedAsInappropriate:
                $this->projectFlaggedAsInappropriate($domainMessage);
                break;
            case $event instanceof GeoCoordinatesUpdated:
                $this->projectGeoCoordinatesUpdated($domainMessage);
                break;
            case $event instanceof ImageAdded:
                $this->projectImageAdded($domainMessage);
                break;
            case $event instanceof ImageRemoved:
                $this->projectImageRemoved($domainMessage);
                break;
            case $event instanceof ImageUpdated:
                $this->projectImageUpdated($domainMessage);
                break;
            case $event instanceof ImagesImportedFromUDB2:
                $this->projectImagesImportedFromUDB2($domainMessage);
                break;
            case $event instanceof ImagesUpdatedFromUDB2:
                $this->projectImagesUpdatedFromUDB2($domainMessage);
                break;
            case $event instanceof VideoAdded:
                $this->projectVideoAdded($domainMessage);
                break;
            case $event instanceof VideoDeleted:
                $this->projectVideoDeleted($domainMessage);
                break;
            case $event instanceof VideoUpdated:
                $this->projectVideoUpdated($domainMessage);
                break;
            case $event instanceof LabelAdded:
                $this->projectLabelAdded($domainMessage);
                break;
            case $event instanceof LabelRemoved:
                $this->projectLabelRemoved($domainMessage);
                break;
            case $event instanceof LabelsReplacedEvent:
            case $event instanceof LabelsReplacedPlace:
                // Needs to above LabelsImported
                $this->projectLabelsReplaced($domainMessage);
                break;
            case $event instanceof LabelsImported:
                $this->projectLabelsImported($domainMessage);
                break;
            case $event instanceof LocationUpdated:
                $this->projectLocationUpdated($domainMessage);
                break;
            case $event instanceof MainImageSelected:
                $this->projectMainImageSelected($domainMessage);
                break;
            case $event instanceof MajorInfoUpdated:
                $this->projectMajorInfoUpdated($domainMessage);
                break;
            case $event instanceof OrganizerDeleted:
                $this->projectOrganizerDeleted($domainMessage);
                break;
            case $event instanceof OrganizerUpdated:
                $this->projectOrganizerUpdated($domainMessage);
                break;
            case $event instanceof PriceInfoUpdated:
                $this->projectPriceInfoUpdated($domainMessage);
                break;
            case $event instanceof Published:
                $this->projectPublished($domainMessage);
                break;
            case $event instanceof Rejected:
                $this->projectRejected($domainMessage);
                break;
            case $event instanceof AvailableFromUpdated:
                $this->projectAvailableFromUpdated($domainMessage);
                break;
            case $event instanceof ThemeUpdated:
                $this->projectThemeUpdated($domainMessage);
                break;
            case $event instanceof ThemeRemoved:
                $this->writeHistory(
                    $event->getItemId(),
                    Log::createFromDomainMessage($domainMessage, 'Thema verwijderd')
                );
                break;
            case $event instanceof TitleTranslated:
                $this->projectTitleTranslated($domainMessage);
                break;
            case $event instanceof TitleUpdated:
                $this->projectTitleUpdated($domainMessage);
                break;
            case $event instanceof TypeUpdated:
                $this->projectTypeUpdated($domainMessage);
                break;
            case $event instanceof TypicalAgeRangeDeleted:
                $this->projectTypicalAgeRangeDeleted($domainMessage);
                break;
            case $event instanceof TypicalAgeRangeUpdated:
                $this->projectTypicalAgeRangeUpdated($domainMessage);
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

    private function projectAttendanceModeUpdated(DomainMessage $domainMessage): void
    {
        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, 'Deelnamevorm (fysiek / online) aangepast')
        );
    }

    private function projectOnlineUrlUpdated(DomainMessage $domainMessage): void
    {
        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, 'Online url aangepast')
        );
    }

    private function projectOnlineUrlDeleted(DomainMessage $domainMessage): void
    {
        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, 'Online url verwijderd')
        );
    }

    private function projectEventDeleted(DomainMessage $domainMessage): void
    {
        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, 'Event verwijderd uit UiTdatabank')
        );
    }

    private function projectEventImportedFromUDB2(DomainMessage $domainMessage): void
    {
        $event = $domainMessage->getPayload();
        $udb2Event = EventItemFactory::createEventFromCdbXml(
            $event->getCdbXmlNamespaceUri(),
            $event->getCdbXml()
        );

        $udb2Log = Log::createFromDomainMessage($domainMessage, 'Event aangemaakt in UDB2');

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
            Log::createFromDomainMessage($domainMessage, 'Event geïmporteerd uit UDB2')
                ->withoutAuthor()
        );
    }

    private function projectEventUpdatedFromUDB2(DomainMessage $domainMessage): void
    {
        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, 'Event aangepast via UDB2')
                ->withoutAuthor()
        );
    }

    private function projectEventCreated(DomainMessage $domainMessage): void
    {
        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, 'Event aangemaakt in UiTdatabank')
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

    private function projectLocationUpdated(DomainMessage $domainMessage): void
    {
        /* @var LocationUpdated $event */
        $event = $domainMessage->getPayload();

        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage(
                $domainMessage,
                "Locatie aangepast naar '{$event->getLocationId()->toString()}'"
            )
        );
    }

    private function projectThemeUpdated(DomainMessage $domainMessage): void
    {
        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, 'Thema aangepast')
        );
    }
}
