<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\History;

use Broadway\Domain\DomainMessage;
use CultuurNet\UDB3\History\Log;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Offer\Events\AbstractAvailableFromUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractVideoDeleted;
use CultuurNet\UDB3\Offer\Events\AbstractVideoEvent;
use CultuurNet\UDB3\Offer\Events\Image\AbstractImageEvent;
use CultuurNet\UDB3\Offer\Events\Image\AbstractImagesImportedFromUDB2;
use CultuurNet\UDB3\Offer\Events\Image\AbstractImageUpdated;
use CultuurNet\UDB3\Offer\Item\Events\MainImageSelected;
use CultuurNet\UDB3\Offer\Item\Events\Moderation\Published;
use CultuurNet\UDB3\Offer\Item\Events\Moderation\Rejected;
use CultuurNet\UDB3\Offer\Item\Events\OrganizerDeleted;
use CultuurNet\UDB3\Offer\Item\Events\OrganizerUpdated;
use DateTimeInterface;

trait OfferHistoryProjectorTrait
{
    abstract protected function writeHistory(string $itemId, Log $log): void;

    private function projectApproved(DomainMessage $domainMessage): void
    {
        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, 'Goedgekeurd')
        );
    }

    private function projectBookingInfoUpdated(DomainMessage $domainMessage): void
    {
        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, 'Reservatie-info aangepast')
        );
    }

    private function projectCalendarUpdated(DomainMessage $domainMessage): void
    {
        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, 'Kalender-info aangepast')
        );
    }

    private function projectContactPointUpdated(DomainMessage $domainMessage): void
    {
        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, 'Contact-info aangepast')
        );
    }

    private function projectDescriptionTranslated(DomainMessage $domainMessage): void
    {
        $event = $domainMessage->getPayload();

        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, "Beschrijving vertaald ({$event->getLanguage()->toString()})")
        );
    }

    private function projectDescriptionUpdated(DomainMessage $domainMessage): void
    {
        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, 'Beschrijving aangepast')
        );
    }

    private function projectFacilitiesUpdated(DomainMessage $domainMessage): void
    {
        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, 'Voorzieningen aangepast')
        );
    }

    private function projectFlaggedAsDuplicate(DomainMessage $domainMessage): void
    {
        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, 'Afgekeurd als duplicaat')
        );
    }

    private function projectFlaggedAsInappropriate(DomainMessage $domainMessage): void
    {
        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, 'Afgekeurd als ongepast')
        );
    }

    private function projectGeoCoordinatesUpdated(DomainMessage $domainMessage): void
    {
        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, 'Geocoördinaten automatisch aangepast')
        );
    }

    private function projectImageAdded(DomainMessage $domainMessage): void
    {
        /* @var AbstractImageEvent $event */
        $event = $domainMessage->getPayload();
        $mediaObjectId = $event->getImage()->getMediaObjectId();

        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, "Afbeelding '{$mediaObjectId->toString()}' toegevoegd")
        );
    }

    private function projectImageRemoved(DomainMessage $domainMessage): void
    {
        /* @var AbstractImageEvent $event */
        $event = $domainMessage->getPayload();
        $mediaObjectId = $event->getImage()->getMediaObjectId();

        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, "Afbeelding '{$mediaObjectId->toString()}' verwijderd")
        );
    }

    private function projectImageUpdated(DomainMessage $domainMessage): void
    {
        /* @var AbstractImageUpdated $event */
        $event = $domainMessage->getPayload();
        $mediaObjectId = $event->getMediaObjectId();

        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, "Afbeelding '{$mediaObjectId}' aangepast")
        );
    }

    private function projectImagesImportedFromUDB2(DomainMessage $domainMessage): void
    {
        /* @var AbstractImagesImportedFromUDB2 $event */
        $event = $domainMessage->getPayload();

        /* @var Image $image */
        foreach (array_values($event->getImages()->toArray()) as $key => $image) {
            $mediaObjectId = $image->getMediaObjectId();
            $this->writeHistory(
                $domainMessage->getId(),
                Log::createFromDomainMessage(
                    $domainMessage,
                    "Afbeelding '{$mediaObjectId->toString()}' geïmporteerd uit UDB2",
                    (string) $key
                )
            );
        }
    }

    private function projectImagesUpdatedFromUDB2(DomainMessage $domainMessage): void
    {
        /* @var AbstractImagesImportedFromUDB2 $event */
        $event = $domainMessage->getPayload();

        /* @var Image $image */
        foreach (array_values($event->getImages()->toArray()) as $key => $image) {
            $mediaObjectId = $image->getMediaObjectId();
            $this->writeHistory(
                $domainMessage->getId(),
                Log::createFromDomainMessage(
                    $domainMessage,
                    "Afbeelding '{$mediaObjectId->toString()}' aangepast via UDB2",
                    (string) $key
                )
            );
        }
    }

    private function projectVideoAdded(DomainMessage $domainMessage): void
    {
        /* @var AbstractVideoEvent $event */
        $event = $domainMessage->getPayload();

        $this->writeHistory(
            $event->getItemId(),
            Log::createFromDomainMessage($domainMessage, 'Video \'' . $event->getVideo()->getId() . '\' toegevoegd')
        );
    }

    private function projectVideoDeleted(DomainMessage $domainMessage): void
    {
        /* @var AbstractVideoDeleted $event */
        $event = $domainMessage->getPayload();

        $this->writeHistory(
            $event->getItemId(),
            Log::createFromDomainMessage($domainMessage, 'Video \'' . $event->getVideoId() . '\' verwijderd')
        );
    }

    private function projectVideoUpdated(DomainMessage $domainMessage): void
    {
        /* @var AbstractVideoEvent $event */
        $event = $domainMessage->getPayload();

        $this->writeHistory(
            $event->getItemId(),
            Log::createFromDomainMessage($domainMessage, 'Video \'' . $event->getVideo()->getId() . '\' aangepast')
        );
    }

    private function projectLabelAdded(DomainMessage $domainMessage): void
    {
        $event = $domainMessage->getPayload();

        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, "Label '{$event->getLabelName()}' toegepast")
        );
    }

    private function projectLabelRemoved(DomainMessage $domainMessage): void
    {
        $event = $domainMessage->getPayload();

        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, "Label '{$event->getLabelName()}' verwijderd")
        );
    }

    private function projectLabelsImported(DomainMessage $domainMessage): void
    {
        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, 'Labels geïmporteerd uit JSON-LD')
        );
    }

    private function projectMainImageSelected(DomainMessage $domainMessage): void
    {
        /* @var MainImageSelected $event */
        $event = $domainMessage->getPayload();
        $mediaObjectId = $event->getImage()->getMediaObjectId()->toString();

        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, "Hoofdafbeelding geselecteerd: '{$mediaObjectId}'")
        );
    }

    private function projectMajorInfoUpdated(DomainMessage $domainMessage): void
    {
        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, 'MajorInfo aangepast')
        );
    }

    private function projectOrganizerDeleted(DomainMessage $domainMessage): void
    {
        /* @var OrganizerDeleted $event */
        $event = $domainMessage->getPayload();
        $organizerId = $event->getOrganizerId();

        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, "Organisatie '{$organizerId}' verwijderd")
        );
    }

    private function projectOrganizerUpdated(DomainMessage $domainMessage): void
    {
        /* @var OrganizerUpdated $event */
        $event = $domainMessage->getPayload();
        $organizerId = $event->getOrganizerId();

        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, "Organisatie '{$organizerId}' toegevoegd")
        );
    }

    private function projectPriceInfoUpdated(DomainMessage $domainMessage): void
    {
        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, 'Prijs-info aangepast')
        );
    }

    private function projectPublished(DomainMessage $domainMessage): void
    {
        /* @var Published $event */
        $event = $domainMessage->getPayload();
        $date = $event->getPublicationDate()->format(DateTimeInterface::ATOM);

        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, "Gepubliceerd (publicatiedatum: '{$date}')")
        );
    }

    private function projectRejected(DomainMessage $domainMessage): void
    {
        /* @var Rejected $event */
        $event = $domainMessage->getPayload();
        $reason = $event->getReason();

        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, "Afgekeurd, reden: '{$reason}'")
        );
    }

    private function projectAvailableFromUpdated(DomainMessage $domainMessage): void
    {
        /* @var AbstractAvailableFromUpdated $event */
        $event = $domainMessage->getPayload();

        $this->writeHistory(
            $event->getItemId(),
            Log::createFromDomainMessage($domainMessage, 'Publicatiedatum aangepast')
        );
    }

    private function projectTitleTranslated(DomainMessage $domainMessage): void
    {
        $event = $domainMessage->getPayload();

        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, "Titel vertaald ({$event->getLanguage()->toString()})")
        );
    }

    private function projectTitleUpdated(DomainMessage $domainMessage): void
    {
        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, 'Titel aangepast')
        );
    }

    private function projectTypeUpdated(DomainMessage $domainMessage): void
    {
        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, 'Type aangepast')
        );
    }

    private function projectTypicalAgeRangeDeleted(DomainMessage $domainMessage): void
    {
        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, 'Leeftijds-info verwijderd')
        );
    }

    private function projectTypicalAgeRangeUpdated(DomainMessage $domainMessage): void
    {
        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, 'Leeftijds-info aangepast')
        );
    }
}
