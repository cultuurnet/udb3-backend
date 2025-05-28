<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels;

use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventListener;
use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Event\Events\LabelsImported as LabelsImportedEvent;
use CultuurNet\UDB3\Event\Events\LabelsReplaced as LabelsReplacedEvent;
use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;
use CultuurNet\UDB3\LabelEventInterface;
use CultuurNet\UDB3\LabelsImportedEventInterface;
use CultuurNet\UDB3\Offer\Events\AbstractLabelAdded as OfferAbstractLabelAdded;
use CultuurNet\UDB3\Offer\Events\AbstractLabelRemoved as OfferAbstractLabelRemoved;
use CultuurNet\UDB3\Organizer\Events\LabelAdded as OrganizerLabelAdded;
use CultuurNet\UDB3\Organizer\Events\LabelRemoved as OrganizerLabelRemoved;
use CultuurNet\UDB3\Organizer\Events\LabelsImported as OrganizerLabelsImported;
use CultuurNet\UDB3\Place\Events\LabelsImported as LabelsImportedPlace;
use CultuurNet\UDB3\Place\Events\LabelsReplaced as LabelsReplacedPlace;
use CultuurNet\UDB3\UiTPAS\Event\Event\EventCardSystemsUpdated;

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
        } elseif ($this->isLabelsReplaced($payload)) {
            $this->applyLabelsReplaced(
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
    abstract public function applyLabelsReplaced(LabelsImportedEventInterface $labelsReplaced, Metadata $metadata): void;

    /**
     * @param Serializable|EventCardSystemsUpdated $payload
     */
    private function isLabelAdded($payload): bool
    {
        return ($payload instanceof OfferAbstractLabelAdded ||
            $payload instanceof OrganizerLabelAdded);
    }

    /**
     * @param Serializable|EventCardSystemsUpdated $payload
     */
    private function isLabelRemoved($payload): bool
    {
        return ($payload instanceof OfferAbstractLabelRemoved ||
            $payload instanceof OrganizerLabelRemoved);
    }

    /**
     * @param Serializable|EventCardSystemsUpdated $payload
     */
    private function isLabelsImported($payload): bool
    {
        return ($payload instanceof LabelsImportedEvent ||
            $payload instanceof LabelsImportedPlace ||
            $payload instanceof  OrganizerLabelsImported
        );
    }

    /**
     * @param Serializable|EventCardSystemsUpdated $payload
     */
    private function isLabelsReplaced($payload): bool
    {
        return ($payload instanceof LabelsReplacedEvent ||
            $payload instanceof LabelsReplacedPlace);
    }
}
