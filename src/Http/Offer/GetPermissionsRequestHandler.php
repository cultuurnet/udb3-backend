<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\Permission\PermissionVoter;
use CultuurNet\UDB3\StringLiteral;
use Slim\Psr7\Headers;

abstract class GetPermissionsRequestHandler
{
    /**
     * @var Permission[]
     */
    protected array $permissions;

    protected PermissionVoter $permissionVoter;

    /**
     * @param Permission[] $permissions
     */
    public function __construct(
        array $permissions,
        PermissionVoter $permissionVoter
    ) {
        $this->permissions = $permissions;
        $this->permissionVoter = $permissionVoter;
    }

    protected function getPermissions(string $offerId, string $userId): array
    {
        $permissionsToReturn = [];
        foreach ($this->permissions as $permission) {
            $hasPermission = $this->permissionVoter->isAllowed(
                $permission,
                new StringLiteral($offerId),
                new StringLiteral($userId)
            );

            if ($hasPermission) {
                $permissionsToReturn[] = $permission->toString();
            }
        }

        return ['permissions' => $permissionsToReturn];
    }
}
