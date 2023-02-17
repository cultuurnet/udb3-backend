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
    private IriGeneratorInterface $eventContributorsUpdatedIriGenerator;

    private IriGeneratorInterface $placeContributorsUpdatedIriGenerator;

    private IriGeneratorInterface $organizerContributorsUpdatedIriGenerator;


    public function __construct(
        IriGeneratorInterface $eventContributorsUpdatedIriGenerator,
        IriGeneratorInterface $placeContributorsUpdatedIriGenerator,
        IriGeneratorInterface $organizerContributorsUpdatedIriGenerator
    ) {
        $this->eventContributorsUpdatedIriGenerator = $eventContributorsUpdatedIriGenerator;
        $this->placeContributorsUpdatedIriGenerator = $placeContributorsUpdatedIriGenerator;
        $this->organizerContributorsUpdatedIriGenerator = $organizerContributorsUpdatedIriGenerator;
    }

    public function createForItemType(string $id, ItemType $itemType): ContributorsUpdated
    {
        if ($itemType->sameAs(ItemType::event())) {
            return new EventContributorsUpdated(
                $id,
                $this->eventContributorsUpdatedIriGenerator->iri($id)
            );
        }

        if ($itemType->sameAs(ItemType::place())) {
            return new PlaceContributorsUpdated(
                $id,
                $this->placeContributorsUpdatedIriGenerator->iri($id)
            );
        }

        return new OrganizerContributorsUpdated(
            $id,
            $this->organizerContributorsUpdatedIriGenerator->iri($id)
        );
    }
}
