<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label;

use CultuurNet\UDB3\Event\Events\LabelsImported as EventLabelsImported;
use CultuurNet\UDB3\Label\Specifications\LabelEventIsOfEventType;
use CultuurNet\UDB3\Label\Specifications\LabelEventIsOfOrganizerType;
use CultuurNet\UDB3\Label\Specifications\LabelEventIsOfPlaceType;
use CultuurNet\UDB3\Label\ValueObjects\RelationType;
use CultuurNet\UDB3\LabelEventInterface;
use CultuurNet\UDB3\LabelsImportedEventInterface;
use CultuurNet\UDB3\Organizer\Events\LabelsImported as OrganizerLabelsImported;
use CultuurNet\UDB3\Place\Events\LabelsImported as PlaceLabelsImported;

class LabelEventRelationTypeResolver implements LabelEventRelationTypeResolverInterface
{
    /**
     * @var LabelEventIsOfEventType
     */
    private $eventTypeSpecification;

    /**
     * @var LabelEventIsOfPlaceType
     */
    private $placeTypeSpecification;

    /**
     * @var LabelEventIsOfOrganizerType
     */
    private $organizerTypeSpecification;

    public function __construct()
    {
        $this->eventTypeSpecification = new LabelEventIsOfEventType();
        $this->placeTypeSpecification = new LabelEventIsOfPlaceType();
        $this->organizerTypeSpecification = new LabelEventIsOfOrganizerType();
    }

    /**
     * @return RelationType
     * @throws \InvalidArgumentException
     */
    public function getRelationType(LabelEventInterface $labelEvent)
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

    /**
     * @return RelationType
     */
    public function getRelationTypeForImport(LabelsImportedEventInterface $labelsImported)
    {
        if ($labelsImported instanceof EventLabelsImported) {
            return RelationType::event();
        } elseif ($labelsImported instanceof PlaceLabelsImported) {
            return RelationType::place();
        } elseif ($labelsImported instanceof OrganizerLabelsImported) {
            return RelationType::organizer();
        } else {
            $message = $this->createIllegalArgumentMessage($labelsImported);
            throw new \InvalidArgumentException($message);
        }
    }

    /**
     * @param LabelEventInterface|LabelsImportedEventInterface $labelEvent
     * @return string
     */
    private function createIllegalArgumentMessage($labelEvent)
    {
        return 'Event with type ' . get_class($labelEvent) . ' can not be converted to a relation type!';
    }
}
