<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security\Permission;

use CultuurNet\UDB3\Role\ReadModel\Permissions\UserPermissionsReadRepositoryInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use ValueObjects\StringLiteral\StringLiteral;

class UserPermissionVoter implements PermissionVoter
{
    private UserPermissionsReadRepositoryInterface $userPermissionsReadRepository;


    public function __construct(
        UserPermissionsReadRepositoryInterface $userPermissionsReadRepository
    ) {
        $this->userPermissionsReadRepository = $userPermissionsReadRepository;
    }

    public function isAllowed(
        Permission $requiredPermission,
        StringLiteral $itemId,
        StringLiteral $userId
    ): bool {
        $permissions = $this->userPermissionsReadRepository->getPermissions(
            $userId
        );

        foreach ($permissions as $currentPermission) {
            if ($requiredPermission->sameAs($currentPermission)) {
                return true;
            }
        }

        return false;
    }
}
