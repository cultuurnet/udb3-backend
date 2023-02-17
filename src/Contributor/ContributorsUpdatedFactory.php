<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Contributor;

use CultuurNet\UDB3\Event\EventContributorsUpdated;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;
use CultuurNet\UDB3\Organizer\OrganizerContributorsUpdated;
use CultuurNet\UDB3\Place\PlaceContributorsUpdated;

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

    public function createForItemType(string $id, ItemType $itemType): ContributorsUpdated
    {
        if ($itemType->sameAs(ItemType::event())) {
            return new EventContributorsUpdated(
                $id,
                $this->eventGetContributorsIriGenerator->iri($id)
            );
        }

        if ($itemType->sameAs(ItemType::place())) {
            return new PlaceContributorsUpdated(
                $id,
                $this->placeGetContributorsIriGenerator->iri($id)
            );
        }

        return new OrganizerContributorsUpdated(
            $id,
            $this->organizerGetContributorsIriGenerator->iri($id)
        );
    }
}
