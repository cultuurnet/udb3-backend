<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\Permissions\GetPermissionsForGivenUserRequestHandler as GetOfferPermissionsForGivenUserRequestHandler;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Role\ValueObjects\Permission;

final class GetPermissionsForGivenUserRequestHandler extends GetOfferPermissionsForGivenUserRequestHandler
{
    public function getItemId(RouteParameters $routeParameters): string
    {
        return $routeParameters->getOfferId();
    }

    public function getPermissionsToCheck(): array
    {
        return [
            Permission::aanbodBewerken(),
            Permission::aanbodModereren(),
            Permission::aanbodVerwijderen(),
        ];
    }
}
