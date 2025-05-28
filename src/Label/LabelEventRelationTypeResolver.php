<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label;

use CultuurNet\UDB3\Event\Events\LabelsImported as EventLabelsImported;
use CultuurNet\UDB3\Event\Events\LabelsReplaced as EventLabelsReplaced;
use CultuurNet\UDB3\Label\Specifications\LabelEventIsOfEventType;
use CultuurNet\UDB3\Label\Specifications\LabelEventIsOfOrganizerType;
use CultuurNet\UDB3\Label\Specifications\LabelEventIsOfPlaceType;
use CultuurNet\UDB3\Label\ValueObjects\RelationType;
use CultuurNet\UDB3\LabelEventInterface;
use CultuurNet\UDB3\LabelsImportedEventInterface;
use CultuurNet\UDB3\Organizer\Events\LabelsImported as OrganizerLabelsImported;
use CultuurNet\UDB3\Place\Events\LabelsImported as PlaceLabelsImported;
use CultuurNet\UDB3\Place\Events\LabelsReplaced as PlaceLabelsReplaced;

class LabelEventRelationTypeResolver implements LabelEventRelationTypeResolverInterface
{
    private LabelEventIsOfEventType $eventTypeSpecification;

    private LabelEventIsOfPlaceType $placeTypeSpecification;

    private LabelEventIsOfOrganizerType $organizerTypeSpecification;

    public function __construct()
    {
        $this->eventTypeSpecification = new LabelEventIsOfEventType();
        $this->placeTypeSpecification = new LabelEventIsOfPlaceType();
        $this->organizerTypeSpecification = new LabelEventIsOfOrganizerType();
    }

    public function getRelationType(LabelEventInterface $labelEvent): RelationType
    {
        if ($this->eventTypeSpecification->isSatisfiedBy($labelEvent)) {
            $relationType = RelationType::event();
        } elseif ($this->placeTypeSpecification->isSatisfiedBy($labelEvent)) {
            $relationType = RelationType::place();
        } elseif ($this->organizerTypeSpecification->isSatisfiedBy($labelEvent)) {
            $relationType = RelationType::organizer();
        } else {
            $message = $this->createIllegalArgumentMessage($labelEvent);
            throw new \InvalidArgumentException($message);
        }

        return $relationType;
    }

    public function getRelationTypeForImport(LabelsImportedEventInterface $labelsImported): RelationType
    {
        if ($labelsImported instanceof EventLabelsImported) {
            return RelationType::event();
        }

        if ($labelsImported instanceof PlaceLabelsImported) {
            return RelationType::place();
        }

        if ($labelsImported instanceof OrganizerLabelsImported) {
            return RelationType::organizer();
        }

        throw new \InvalidArgumentException($this->createIllegalArgumentMessage($labelsImported));
    }

    public function getRelationTypeForReplaceLabel(LabelsImportedEventInterface $labelsReplaced): RelationType
    {
        if ($labelsReplaced instanceof EventLabelsReplaced) {
            return RelationType::event();
        }

        if ($labelsReplaced instanceof PlaceLabelsReplaced) {
            return RelationType::place();
        }

        //@todo have organizer code

        throw new \InvalidArgumentException($this->createIllegalArgumentMessage($labelsReplaced));
    }

    /**
     * @param LabelEventInterface|LabelsImportedEventInterface $labelEvent
     */
    private function createIllegalArgumentMessage($labelEvent): string
    {
        return 'Event with type ' . get_class($labelEvent) . ' can not be converted to a relation type!';
    }
}
