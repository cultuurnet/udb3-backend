<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Auth;

use CultuurNet\UDB3\Role\ValueObjects\Permission;

final class PermissionRestrictedRouteRule extends RouteRule
{
    private Permission $permission;

    public function __construct(string $pathPattern, array $methods, Permission $permission)
    {
        parent::__construct($pathPattern, $methods);
        $this->permission = $permission;
    }

    public function getPermission(): Permission
    {
        return $this->permission;
    }
}
