<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels;

use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventListener;
use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;
use CultuurNet\UDB3\LabelEventInterface;
use CultuurNet\UDB3\LabelsImportedEventInterface;
use CultuurNet\UDB3\Offer\Events\AbstractLabelAdded as OfferAbstractLabelAdded;
use CultuurNet\UDB3\Offer\Events\AbstractLabelRemoved as OfferAbstractLabelRemoved;
use CultuurNet\UDB3\Offer\Events\AbstractLabelsImported;
use CultuurNet\UDB3\Organizer\Events\LabelAdded as OrganizerLabelAdded;
use CultuurNet\UDB3\Organizer\Events\LabelRemoved as OrganizerLabelRemoved;
use CultuurNet\UDB3\Organizer\Events\LabelsImported;

abstract class AbstractProjector implements EventListener
{
    use DelegateEventHandlingToSpecificMethodTrait {
        DelegateEventHandlingToSpecificMethodTrait::handle as handleSpecific;
    }

    public function handle(DomainMessage $domainMessage): void
    {
        $payload = $domainMessage->getPayload();

        if ($this->isLabelAdded($payload)) {
            $this->applyLabelAdded(
                $domainMessage->getPayload(),
                $domainMessage->getMetadata()
            );
        } elseif ($this->isLabelRemoved($payload)) {
            $this->applyLabelRemoved(
                $domainMessage->getPayload(),
                $domainMessage->getMetadata()
            );
        } elseif ($this->isLabelsImported($payload)) {
            $this->applyLabelsImported(
                $domainMessage->getPayload(),
                $domainMessage->getMetadata()
            );
        } else {
            $this->handleSpecific($domainMessage);
        }
    }

    abstract public function applyLabelAdded(LabelEventInterface $labelAdded, Metadata $metadata): void;

    abstract public function applyLabelRemoved(LabelEventInterface $labelRemoved, Metadata $metadata): void;

    abstract public function applyLabelsImported(LabelsImportedEventInterface $labelsImported, Metadata $metadata): void;

    private function isLabelAdded(Serializable $payload): bool
    {
        return ($payload instanceof OfferAbstractLabelAdded ||
            $payload instanceof OrganizerLabelAdded);
    }

    private function isLabelRemoved(Serializable $payload): bool
    {
        return ($payload instanceof OfferAbstractLabelRemoved ||
            $payload instanceof OrganizerLabelRemoved);
    }

    private function isLabelsImported(Serializable $payload): bool
    {
        return ($payload instanceof AbstractLabelsImported ||
            $payload instanceof LabelsImported);
    }
}
