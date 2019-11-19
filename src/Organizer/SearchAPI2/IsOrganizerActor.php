<?php

namespace CultuurNet\UDB3\Organizer\SearchAPI2;

use CultuurNet\Search\Parameter\FilterQuery;
use CultuurNet\UDB3\SearchAPI2\Filters\SearchFilterInterface;

class IsOrganizerActor implements SearchFilterInterface
{
    public function apply(array $searchParameters)
    {
        $searchParameters[] = new FilterQuery('type:actor AND category_actortype_id:8.11.0.0.0');

        return $searchParameters;
    }
}
