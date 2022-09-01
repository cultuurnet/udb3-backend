<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use CultuurNet\UDB3\Http\Permissions\GetPermissionsForGivenUserRequestHandler as GetOrganizerPermissionsForGivenUserRequestHandler;
use CultuurNet\UDB3\Http\Request\RouteParameters;

final class GetPermissionsForGivenUserRequestHandler extends GetOrganizerPermissionsForGivenUserRequestHandler
{
    public function getItemId(RouteParameters $routeParameters): string
    {
        return $routeParameters->getOrganizerId();
    }
}
