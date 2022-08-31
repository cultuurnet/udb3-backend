<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\Permission\PermissionVoter;
use CultuurNet\UDB3\StringLiteral;

abstract class HasPermissionRequestHandler
{
    protected Permission $permission;

    protected PermissionVoter $permissionVoter;

    public function __construct(
        Permission $permission,
        PermissionVoter $permissionVoter
    ) {
        $this->permission = $permission;
        $this->permissionVoter = $permissionVoter;
    }

    protected function hasPermission(string $offerId, ?string $userId = null): array
    {
        if ($userId) {
            $hasPermission = $this->permissionVoter->isAllowed(
                $this->permission,
                new StringLiteral($offerId),
                new StringLiteral($userId)
            );
        } else {
            $hasPermission = false;
        }

        return ['hasPermission' => $hasPermission];
    }
}
