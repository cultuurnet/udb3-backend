<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\History;

use Broadway\Domain\DomainMessage;
use CultuurNet\UDB3\History\Log;
use CultuurNet\UDB3\Place\Events\DescriptionTranslated;
use CultuurNet\UDB3\Place\Events\LabelAdded;
use CultuurNet\UDB3\Place\Events\LabelRemoved;
use CultuurNet\UDB3\Place\Events\TitleTranslated;

trait OfferHistoryProjectorTrait
{
    abstract protected function createGenericLog(DomainMessage $domainMessage, string $description);
    abstract protected function writeHistory(string $itemId, Log $log);

    private function projectLabelAdded(LabelAdded $event, DomainMessage $domainMessage): void
    {
        $this->writeHistory(
            $event->getItemId(),
            $this->createGenericLog($domainMessage, "Label '{$event->getLabel()}' toegepast")
        );
    }

    private function projectLabelRemoved(LabelRemoved $event, DomainMessage $domainMessage): void
    {
        $this->writeHistory(
            $event->getItemId(),
            $this->createGenericLog($domainMessage, "Label '{$event->getLabel()}' verwijderd")
        );
    }

    private function projectDescriptionTranslated(DescriptionTranslated $event, DomainMessage $domainMessage)
    {
        $this->writeHistory(
            $event->getItemId(),
            $this->createGenericLog($domainMessage, "Beschrijving vertaald ({$event->getLanguage()})")
        );
    }

    private function projectTitleTranslated(TitleTranslated $event, DomainMessage $domainMessage)
    {
        $this->writeHistory(
            $event->getItemId(),
            $this->createGenericLog($domainMessage, "Titel vertaald ({$event->getLanguage()})")
        );
    }
}
