<?php

namespace CultuurNet\UDB3\Role\ReadModel\Permissions;

use Broadway\EventHandling\EventListenerInterface;
use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;
use CultuurNet\UDB3\Role\Events\PermissionAdded;
use CultuurNet\UDB3\Role\Events\PermissionRemoved;
use CultuurNet\UDB3\Role\Events\RoleDeleted;
use CultuurNet\UDB3\Role\Events\UserAdded;
use CultuurNet\UDB3\Role\Events\UserRemoved;

class UserPermissionsProjector implements EventListenerInterface
{
    use DelegateEventHandlingToSpecificMethodTrait;

    /**
     * @var UserPermissionsWriteRepositoryInterface
     */
    private $repository;

    /**
     * UserPermissionsProjector constructor.
     * @param UserPermissionsWriteRepositoryInterface $repository
     */
    public function __construct(UserPermissionsWriteRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param RoleDeleted $roleDeleted
     */
    public function applyRoleDeleted(RoleDeleted $roleDeleted)
    {
        $this->repository->removeRole($roleDeleted->getUuid());
    }

    /**
     * @param UserAdded $userAdded
     */
    public function applyUserAdded(UserAdded $userAdded)
    {
        $this->repository->addUserRole($userAdded->getUserId(), $userAdded->getUuid());
    }

    /**
     * @param UserRemoved $userRemoved
     */
    public function applyUserRemoved(UserRemoved $userRemoved)
    {
        $this->repository->removeUserRole($userRemoved->getUserId(), $userRemoved->getUuid());
    }

    /**
     * @param PermissionAdded $permissionAdded
     */
    public function applyPermissionAdded(PermissionAdded $permissionAdded)
    {
        $this->repository->addRolePermission($permissionAdded->getUuid(), $permissionAdded->getPermission());
    }

    /**
     * @param PermissionRemoved $permissionRemoved
     */
    public function applyPermissionRemoved(PermissionRemoved $permissionRemoved)
    {
        $this->repository->removeRolePermission($permissionRemoved->getUuid(), $permissionRemoved->getPermission());
    }
}
