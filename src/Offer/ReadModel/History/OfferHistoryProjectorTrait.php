<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\History;

use Broadway\Domain\DomainMessage;
use CultuurNet\UDB3\History\Log;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Offer\Events\Image\AbstractImageEvent;
use CultuurNet\UDB3\Offer\Events\Image\AbstractImagesImportedFromUDB2;
use CultuurNet\UDB3\Offer\Events\Image\AbstractImageUpdated;
use CultuurNet\UDB3\Offer\Item\Events\MainImageSelected;

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
            Log::createFromDomainMessage($domainMessage, "Beschrijving vertaald ({$event->getLanguage()})")
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
            Log::createFromDomainMessage($domainMessage, "Afbeelding '{$mediaObjectId}' toegevoegd")
        );
    }

    private function projectImageRemoved(DomainMessage $domainMessage): void
    {
        /* @var AbstractImageEvent $event */
        $event = $domainMessage->getPayload();
        $mediaObjectId = $event->getImage()->getMediaObjectId();

        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, "Afbeelding '{$mediaObjectId}' verwijderd")
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
                    "Afbeelding '{$mediaObjectId}' geïmporteerd uit UDB2",
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
                    "Afbeelding '{$mediaObjectId}' aangepast via UDB2",
                    (string) $key
                )
            );
        }
    }

    private function projectLabelAdded(DomainMessage $domainMessage): void
    {
        $event = $domainMessage->getPayload();

        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, "Label '{$event->getLabel()}' toegepast")
        );
    }

    private function projectLabelRemoved(DomainMessage $domainMessage): void
    {
        $event = $domainMessage->getPayload();

        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, "Label '{$event->getLabel()}' verwijderd")
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
        $mediaObjectId = $event->getImage()->getMediaObjectId()->toNative();

        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, "Hoofdafbeelding geselecteerd: '{$mediaObjectId}'")
        );
    }

    private function projectTitleTranslated(DomainMessage $domainMessage): void
    {
        $event = $domainMessage->getPayload();

        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, "Titel vertaald ({$event->getLanguage()})")
        );
    }
}
