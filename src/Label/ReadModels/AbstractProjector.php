<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels;

use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventListener;
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


    public function handle(DomainMessage $domainMessage)
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


    abstract public function applyLabelAdded(LabelEventInterface $labelAdded, Metadata $metadata);


    abstract public function applyLabelRemoved(LabelEventInterface $labelRemoved, Metadata $metadata);


    abstract public function applyLabelsImported(LabelsImportedEventInterface $labelsImported, Metadata $metadata);

    /**
     * @return bool
     */
    private function isLabelAdded($payload)
    {
        return ($payload instanceof OfferAbstractLabelAdded ||
            $payload instanceof OrganizerLabelAdded);
    }

    /**
     * @return bool
     */
    private function isLabelRemoved($payload)
    {
        return ($payload instanceof OfferAbstractLabelRemoved ||
            $payload instanceof OrganizerLabelRemoved);
    }

    /**
     * @return bool
     */
    private function isLabelsImported($payload)
    {
        return ($payload instanceof AbstractLabelsImported ||
            $payload instanceof LabelsImported);
    }
}
