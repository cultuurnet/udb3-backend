<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use CultuurNet\UDB3\Http\Permissions\GetPermissionsForCurrentUserRequestHandler as GetOrganizerPermissionsForCurrentUserRequestHandler;
use CultuurNet\UDB3\Http\Request\RouteParameters;

final class GetPermissionsForCurrentUserRequestHandler extends GetOrganizerPermissionsForCurrentUserRequestHandler
{
    public function getItemId(RouteParameters $routeParameters): string
    {
        return $routeParameters->getOrganizerId();
    }
}
