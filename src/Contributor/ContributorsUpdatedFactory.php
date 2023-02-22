<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Contributor;

use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Event\Events\EventProjectedToJSONLD;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;
use CultuurNet\UDB3\Organizer\OrganizerProjectedToJSONLD;
use CultuurNet\UDB3\Place\Events\PlaceProjectedToJSONLD;

final class ContributorsUpdatedFactory
{
    private IriGeneratorInterface $eventGetContributorsIriGenerator;

    private IriGeneratorInterface $placeGetContributorsIriGenerator;

    private IriGeneratorInterface $organizerGetContributorsIriGenerator;


    public function __construct(
        IriGeneratorInterface $eventGetContributorsIriGenerator,
        IriGeneratorInterface $placeGetContributorsIriGenerator,
        IriGeneratorInterface $organizerGetContributorsIriGenerator
    ) {
        $this->eventGetContributorsIriGenerator = $eventGetContributorsIriGenerator;
        $this->placeGetContributorsIriGenerator = $placeGetContributorsIriGenerator;
        $this->organizerGetContributorsIriGenerator = $organizerGetContributorsIriGenerator;
    }

    public function createForItemType(string $id, ItemType $itemType): Serializable
    {
        if ($itemType->sameAs(ItemType::event())) {
            return new EventProjectedToJSONLD(
                $id,
                $this->eventGetContributorsIriGenerator->iri($id)
            );
        }

        if ($itemType->sameAs(ItemType::place())) {
            return new PlaceProjectedToJSONLD(
                $id,
                $this->placeGetContributorsIriGenerator->iri($id)
            );
        }

        return new OrganizerProjectedToJSONLD(
            $id,
            $this->organizerGetContributorsIriGenerator->iri($id)
        );
    }
}
