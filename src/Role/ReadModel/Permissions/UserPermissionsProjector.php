<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ReadModel\Permissions;

use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;
use CultuurNet\UDB3\Role\Events\PermissionAdded;
use CultuurNet\UDB3\Role\Events\PermissionRemoved;
use CultuurNet\UDB3\Role\Events\RoleDeleted;
use CultuurNet\UDB3\Role\Events\UserAdded;
use CultuurNet\UDB3\Role\Events\UserRemoved;

class UserPermissionsProjector implements EventListener
{
    use DelegateEventHandlingToSpecificMethodTrait;

    private UserPermissionsWriteRepositoryInterface $repository;

    public function __construct(UserPermissionsWriteRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function applyRoleDeleted(RoleDeleted $roleDeleted): void
    {
        $this->repository->removeRole($roleDeleted->getUuid());
    }


    public function applyUserAdded(UserAdded $userAdded): void
    {
        $this->repository->addUserRole($userAdded->getUserId(), $userAdded->getUuid());
    }


    public function applyUserRemoved(UserRemoved $userRemoved): void
    {
        $this->repository->removeUserRole($userRemoved->getUserId(), $userRemoved->getUuid());
    }


    public function applyPermissionAdded(PermissionAdded $permissionAdded): void
    {
        $this->repository->addRolePermission($permissionAdded->getUuid(), $permissionAdded->getPermission());
    }


    public function applyPermissionRemoved(PermissionRemoved $permissionRemoved): void
    {
        $this->repository->removeRolePermission($permissionRemoved->getUuid(), $permissionRemoved->getPermission());
    }
}
