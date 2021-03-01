<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\Relations;

interface RepositoryInterface
{
    public function storeRelations($placeId, $organizerId);

    public function removeRelations($placeId);

    public function getPlacesOrganizedByOrganizer($organizerId);
}
