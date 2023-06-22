<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\Relations;

use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;
use CultuurNet\UDB3\Place\Events\OrganizerDeleted;
use CultuurNet\UDB3\Place\Events\OrganizerUpdated;
use CultuurNet\UDB3\Place\Events\PlaceDeleted;
use CultuurNet\UDB3\Place\Events\PlaceImportedFromUDB2;

final class PlaceRelationsProjector implements EventListener
{
    use DelegateEventHandlingToSpecificMethodTrait;

    protected PlaceRelationsRepository $repository;

    public function __construct(PlaceRelationsRepository $repository)
    {
        $this->repository = $repository;
    }

    protected function applyPlaceImportedFromUDB2(PlaceImportedFromUDB2 $place): void
    {
        // No relation exists in UDB2.
        $placeId = $place->getActorId();
        $this->storeRelations($placeId, null);
    }

    protected function applyPlaceDeleted(PlaceDeleted $place): void
    {
        $placeId = $place->getItemId();
        $this->repository->removeRelations($placeId);
    }

    protected function applyOrganizerUpdated(OrganizerUpdated $organizerUpdated): void
    {
        $this->storeRelations(
            $organizerUpdated->getItemId(),
            $organizerUpdated->getOrganizerId()
        );
    }

    protected function applyOrganizerDeleted(OrganizerDeleted $organizerDeleted): void
    {
        $this->storeRelations($organizerDeleted->getItemId(), null);
    }

    protected function storeRelations(string $placeId, ?string $organizerId): void
    {
        $this->repository->storeRelations($placeId, $organizerId);
    }
}
