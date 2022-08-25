<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security\Permission;

use CultuurNet\UDB3\Role\ReadModel\Permissions\UserPermissionsReadRepositoryInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\StringLiteral;

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
        return $this->userPermissionsReadRepository->hasPermission($userId->toNative(), $requiredPermission);
    }
}
