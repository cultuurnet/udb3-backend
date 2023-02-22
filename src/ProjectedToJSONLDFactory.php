<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Event\Events\EventProjectedToJSONLD;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;
use CultuurNet\UDB3\Organizer\OrganizerProjectedToJSONLD;
use CultuurNet\UDB3\Place\Events\PlaceProjectedToJSONLD;

final class ProjectedToJSONLDFactory
{
    private IriGeneratorInterface $eventIriGenerator;

    private IriGeneratorInterface $placeIriGenerator;

    private IriGeneratorInterface $organizerIriGenerator;

    public function __construct(
        IriGeneratorInterface $eventIriGenerator,
        IriGeneratorInterface $placeIriGenerator,
        IriGeneratorInterface $organizerIriGenerator
    ) {
        $this->eventIriGenerator = $eventIriGenerator;
        $this->placeIriGenerator = $placeIriGenerator;
        $this->organizerIriGenerator = $organizerIriGenerator;
    }

    public function createForItemType(string $id, ItemType $itemType): Serializable
    {
        if ($itemType->sameAs(ItemType::event())) {
            return new EventProjectedToJSONLD(
                $id,
                $this->eventIriGenerator->iri($id)
            );
        }

        if ($itemType->sameAs(ItemType::place())) {
            return new PlaceProjectedToJSONLD(
                $id,
                $this->placeIriGenerator->iri($id)
            );
        }

        return new OrganizerProjectedToJSONLD(
            $id,
            $this->organizerIriGenerator->iri($id)
        );
    }
}
