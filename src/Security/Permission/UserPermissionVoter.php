<?php

namespace CultuurNet\UDB3\Security\Permission;

use CultuurNet\UDB3\Offer\Security\Permission\PermissionVoterInterface;
use CultuurNet\UDB3\Role\ReadModel\Permissions\UserPermissionsReadRepositoryInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use ValueObjects\StringLiteral\StringLiteral;

class UserPermissionVoter implements PermissionVoterInterface
{
    /**
     * @var UserPermissionsReadRepositoryInterface
     */
    private $userPermissionsReadRepository;

    /**
     * @param UserPermissionsReadRepositoryInterface $userPermissionsReadRepository
     */
    public function __construct(
        UserPermissionsReadRepositoryInterface $userPermissionsReadRepository
    ) {
        $this->userPermissionsReadRepository = $userPermissionsReadRepository;
    }

    /**
     * @inheritdoc
     */
    public function isAllowed(
        Permission $requiredPermission,
        StringLiteral $offerId,
        StringLiteral $userId
    ) {
        $permissions = $this->userPermissionsReadRepository->getPermissions(
            $userId
        );

        foreach ($permissions as $currentPermission) {
            if ($requiredPermission === $currentPermission) {
                return true;
            }
        }

        return false;
    }
}
