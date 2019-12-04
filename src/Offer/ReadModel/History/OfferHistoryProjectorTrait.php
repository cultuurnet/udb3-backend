<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\History;

use Broadway\Domain\DomainMessage;
use CultuurNet\UDB3\History\Log;
use CultuurNet\UDB3\Offer\Events\AbstractDescriptionTranslated;
use CultuurNet\UDB3\Offer\Events\AbstractLabelAdded;
use CultuurNet\UDB3\Offer\Events\AbstractLabelRemoved;
use CultuurNet\UDB3\Offer\Events\AbstractTitleTranslated;

trait OfferHistoryProjectorTrait
{
    abstract protected function createGenericLog(DomainMessage $domainMessage, string $description);
    abstract protected function writeHistory(string $itemId, Log $log);

    private function projectLabelAdded(AbstractLabelAdded $event, DomainMessage $domainMessage): void
    {
        $this->writeHistory(
            $event->getItemId(),
            $this->createGenericLog($domainMessage, "Label '{$event->getLabel()}' toegepast")
        );
    }

    private function projectLabelRemoved(AbstractLabelRemoved $event, DomainMessage $domainMessage): void
    {
        $this->writeHistory(
            $event->getItemId(),
            $this->createGenericLog($domainMessage, "Label '{$event->getLabel()}' verwijderd")
        );
    }

    private function projectDescriptionTranslated(AbstractDescriptionTranslated $event, DomainMessage $domainMessage)
    {
        $this->writeHistory(
            $event->getItemId(),
            $this->createGenericLog($domainMessage, "Beschrijving vertaald ({$event->getLanguage()})")
        );
    }

    private function projectTitleTranslated(AbstractTitleTranslated $event, DomainMessage $domainMessage)
    {
        $this->writeHistory(
            $event->getItemId(),
            $this->createGenericLog($domainMessage, "Titel vertaald ({$event->getLanguage()})")
        );
    }
}
