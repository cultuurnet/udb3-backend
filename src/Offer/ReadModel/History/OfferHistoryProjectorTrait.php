<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\History;

use Broadway\Domain\DomainMessage;
use CultuurNet\UDB3\History\Log;

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

    private function projectDescriptionTranslated(DomainMessage $domainMessage): void
    {
        $event = $domainMessage->getPayload();

        $this->writeHistory(
            $domainMessage->getId(),
            Log::createFromDomainMessage($domainMessage, "Beschrijving vertaald ({$event->getLanguage()})")
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
