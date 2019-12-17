<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\History;

use Broadway\Domain\DomainMessage;
use CultuurNet\UDB3\Cdb\ActorItemFactory;
use CultuurNet\UDB3\History\BaseHistoryProjector;
use CultuurNet\UDB3\History\Log;
use CultuurNet\UDB3\Offer\ReadModel\History\OfferHistoryProjectorTrait;
use CultuurNet\UDB3\Place\Events\AddressTranslated;
use CultuurNet\UDB3\Place\Events\AddressUpdated;
use CultuurNet\UDB3\Place\Events\BookingInfoUpdated;
use CultuurNet\UDB3\Place\Events\CalendarUpdated;
use CultuurNet\UDB3\Place\Events\ContactPointUpdated;
use CultuurNet\UDB3\Place\Events\DescriptionTranslated;
use CultuurNet\UDB3\Place\Events\DescriptionUpdated;
use CultuurNet\UDB3\Place\Events\FacilitiesUpdated;
use CultuurNet\UDB3\Place\Events\GeoCoordinatesUpdated;
use CultuurNet\UDB3\Place\Events\Image\ImagesImportedFromUDB2;
use CultuurNet\UDB3\Place\Events\Image\ImagesUpdatedFromUDB2;
use CultuurNet\UDB3\Place\Events\ImageAdded;
use CultuurNet\UDB3\Place\Events\ImageRemoved;
use CultuurNet\UDB3\Place\Events\ImageUpdated;
use CultuurNet\UDB3\Place\Events\LabelAdded;
use CultuurNet\UDB3\Place\Events\LabelRemoved;
use CultuurNet\UDB3\Place\Events\LabelsImported;
use CultuurNet\UDB3\Place\Events\MainImageSelected;
use CultuurNet\UDB3\Place\Events\MajorInfoUpdated;
use CultuurNet\UDB3\Place\Events\MarkedAsCanonical;
use CultuurNet\UDB3\Place\Events\MarkedAsDuplicate;
use CultuurNet\UDB3\Place\Events\Moderation\Approved;
use CultuurNet\UDB3\Place\Events\Moderation\FlaggedAsDuplicate;
use CultuurNet\UDB3\Place\Events\Moderation\FlaggedAsInappropriate;
use CultuurNet\UDB3\Place\Events\OrganizerDeleted;
use CultuurNet\UDB3\Place\Events\OrganizerUpdated;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Place\Events\PlaceDeleted;
use CultuurNet\UDB3\Place\Events\PlaceImportedFromUDB2;
use CultuurNet\UDB3\Place\Events\PlaceUpdatedFromUDB2;
use CultuurNet\UDB3\Place\Events\TitleTranslated;
use DateTime;
use DateTimeZone;

final class HistoryProjector extends BaseHistoryProjector
{
    use OfferHistoryProjectorTrait;

    public function handle(DomainMessage $domainMessage): void
    {
        $event = $domainMessage->getPayload();
        switch (true) {
            case $event instanceof AddressTranslated:
                $this->projectAddressTranslated($domainMessage);
                break;
            case $event instanceof AddressUpdated:
                $this->projectAddressUpdated($domainMessage);
                break;
            case $event instanceof Approved:
                $this->projectApproved($domainMessage);
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
            case $event instanceof LabelAdded:
                $this->projectLabelAdded($domainMessage);
                break;
            case $event instanceof LabelRemoved:
                $this->projectLabelRemoved($domainMessage);
                break;
            case $event instanceof LabelsImported:
                $this->projectLabelsImported($domainMessage);
                break;
            case $event instanceof MainImageSelected:
                $this->projectMainImageSelected($domainMessage);
                break;
            case $event instanceof MajorInfoUpdated:
                $this->projectMajorInfoUpdated($domainMessage);
                break;
            case $event instanceof MarkedAsCanonical:
                $this->projectMarkedAsCanonical($domainMessage);
                break;
            case $event instanceof MarkedAsDuplicate:
                $this->projectMarkedAsDuplicate($domainMessage);
                break;
            case $event instanceof OrganizerDeleted:
                $this->projectOrganizerDeleted($domainMessage);
                break;
            case $event instanceof OrganizerUpdated:
                $this->projectOrganizerUpdated($domainMessage);
                break;
            case $event instanceof PlaceCreated:
                $this->projectPlaceCreated($domainMessage);
                break;
            case $event instanceof PlaceDeleted:
                $this->projectPlaceDeleted($domainMessage);
                break;
            case $event instanceof PlaceImportedFromUDB2:
                $this->projectPlaceImportedFromUDB2($domainMessage);
                break;
            case $event instanceof PlaceUpdatedFromUDB2:
                $this->projectPlaceUpdatedFromUDB2($domainMessage);
                break;
            case $event instanceof TitleTranslated:
                $this->projectTitleTranslated($domainMessage);
                break;
        }
    }

    private function projectAddressTranslated(DomainMessage $domainMessage): void
    {
        /* @var AddressTranslated $event */
        $event = $domainMessage->getPayload();
        $lang = $event->getLanguage()->getCode();

        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, "Adres vertaald ({$lang})")
        );
    }

    private function projectAddressUpdated(DomainMessage $domainMessage): void
    {
        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, 'Adres aangepast')
        );
    }

    private function projectMarkedAsCanonical(DomainMessage $domainMessage): void
    {
        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, 'Locatie gemarkeerd als canonical')
        );
    }

    private function projectMarkedAsDuplicate(DomainMessage $domainMessage): void
    {
        /* @var MarkedAsDuplicate $event */
        $event = $domainMessage->getPayload();
        $canonicalId = $event->getDuplicateOf();

        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, "Locatie gemarkeerd als duplicaat van '{$canonicalId}'")
        );
    }

    private function projectPlaceCreated(DomainMessage $domainMessage): void
    {
        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, 'Locatie aangemaakt in UiTdatabank')
        );
    }

    private function projectPlaceDeleted(DomainMessage $domainMessage): void
    {
        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, 'Locatie verwijderd uit UiTdatabank')
        );
    }

    private function projectPlaceImportedFromUDB2(DomainMessage $domainMessage): void
    {
        $event = $domainMessage->getPayload();

        $udb2Actor = ActorItemFactory::createActorFromCdbXml(
            $event->getCdbXmlNamespaceUri(),
            $event->getCdbXml()
        );

        $udb2Log = Log::createFromDomainMessage($domainMessage, 'Locatie aangemaakt in UDB2');

        if ($udb2Actor->getCreatedBy()) {
            $udb2Log = $udb2Log->withAuthor($udb2Actor->getCreatedBy());
        }

        $udb2Date = DateTime::createFromFormat(
            'Y-m-d?H:i:s',
            $udb2Actor->getCreationDate(),
            new DateTimeZone('Europe/Brussels')
        );
        if ($udb2Date) {
            $udb2Log = $udb2Log->withDate($udb2Date);
        }

        $this->writeHistory($domainMessage->getId(), $udb2Log);

        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, 'Locatie geïmporteerd uit UDB2')
                ->withoutAuthor()
        );
    }

    private function projectPlaceUpdatedFromUDB2(DomainMessage $domainMessage): void
    {
        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, 'Locatie aangepast via UDB2')
                ->withoutAuthor()
        );
    }
}
