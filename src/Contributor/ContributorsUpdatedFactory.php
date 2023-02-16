<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Contributor;

use CultuurNet\UDB3\Event\EventContributorsUpdated;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
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

    public function createEventContributorsUpdated(string $id): EventContributorsUpdated
    {
        return new EventContributorsUpdated(
            $id,
            $this->eventContributorsUpdatedIriGenerator->iri($id)
        );
    }

    public function createPlaceContributorsUpdated(string $id): PlaceContributorsUpdated
    {
        return new PlaceContributorsUpdated(
            $id,
            $this->placeContributorsUpdatedIriGenerator->iri($id)
        );
    }

    public function createOrganizerContributorsUpdated(string $id): OrganizerContributorsUpdated
    {
        return new OrganizerContributorsUpdated(
            $id,
            $this->organizerContributorsUpdatedIriGenerator->iri($id)
        );
    }
}
