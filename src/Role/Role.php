<?php

namespace CultuurNet\UDB3\Role;

use Broadway\EventSourcing\EventSourcedAggregateRoot;
use CultuurNet\UDB3\Role\Events\ConstraintAdded;
use CultuurNet\UDB3\Role\Events\ConstraintRemoved;
use CultuurNet\UDB3\Role\Events\ConstraintUpdated;
use CultuurNet\UDB3\Role\Events\LabelAdded;
use CultuurNet\UDB3\Role\Events\LabelRemoved;
use CultuurNet\UDB3\Role\Events\PermissionAdded;
use CultuurNet\UDB3\Role\Events\PermissionRemoved;
use CultuurNet\UDB3\Role\Events\RoleCreated;
use CultuurNet\UDB3\Role\Events\RoleDeleted;
use CultuurNet\UDB3\Role\Events\RoleRenamed;
use CultuurNet\UDB3\Role\Events\UserAdded;
use CultuurNet\UDB3\Role\Events\UserRemoved;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Role\ValueObjects\Query;
use CultuurNet\UDB3\ValueObject\SapiVersion;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

class Role extends EventSourcedAggregateRoot
{
    /**
     * @var UUID
     */
    private $uuid;

    /**
     * @var StringLiteral
     */
    private $name;

    /**
     * @var Query[]
     */
    private $queries = [];

    /**
     * @var Permission[]
     */
    private $permissions = [];

    /**
     * @var UUID[]
     */
    private $labelIds = [];

    /**
     * @var StringLiteral[]
     */
    private $userIds = [];

    /**
     * @return string
     */
    public function getAggregateRootId()
    {
        return $this->uuid;
    }

    /**
     * @return Role
     */
    public static function create(
        UUID $uuid,
        StringLiteral $name
    ) {
        $role = new Role();

        $role->apply(new RoleCreated(
            $uuid,
            $name
        ));

        return $role;
    }

    /**
     * Rename the role.
     *
     */
    public function rename(
        UUID $uuid,
        StringLiteral $name
    ) {
        $this->apply(new RoleRenamed($uuid, $name));
    }


    public function addConstraint(SapiVersion $sapiVersion, Query $query): void
    {
        if ($this->queryEmpty($sapiVersion)) {
            $this->apply(new ConstraintAdded($this->uuid, $sapiVersion, $query));
        }
    }


    public function updateConstraint(SapiVersion $sapiVersion, Query $query): void
    {
        if (!$this->queryEmpty($sapiVersion) &&
            !$this->querySameValue($sapiVersion, $query)) {
            $this->apply(new ConstraintUpdated($this->uuid, $sapiVersion, $query));
        }
    }


    public function removeConstraint(SapiVersion $sapiVersion): void
    {
        if (!$this->queryEmpty($sapiVersion)) {
            $this->apply(new ConstraintRemoved($this->uuid, $sapiVersion));
        }
    }


    private function queryEmpty(SapiVersion $sapiVersion): bool
    {
        return empty($this->queries[$sapiVersion->toNative()]);
    }


    private function querySameValue(SapiVersion $sapiVersion, Query $query): bool
    {
        return $this->queries[$sapiVersion->toNative()]->sameValueAs($query);
    }

    /**
     * Add a permission to the role.
     *
     */
    public function addPermission(
        UUID $uuid,
        Permission $permission
    ) {
        if (!in_array($permission, $this->permissions)) {
            $this->apply(new PermissionAdded($uuid, $permission));
        }
    }

    /**
     * Remove a permission from the role.
     *
     */
    public function removePermission(
        UUID $uuid,
        Permission $permission
    ) {
        if (in_array($permission, $this->permissions)) {
            $this->apply(new PermissionRemoved($uuid, $permission));
        }
    }


    public function addLabel(
        UUID $labelId
    ) {
        if (!in_array($labelId, $this->labelIds)) {
            $this->apply(new LabelAdded($this->uuid, $labelId));
        }
    }


    public function removeLabel(
        UUID $labelId
    ) {
        if (in_array($labelId, $this->labelIds)) {
            $this->apply(new LabelRemoved($this->uuid, $labelId));
        }
    }


    public function addUser(
        StringLiteral $userId
    ) {
        if (!in_array($userId, $this->userIds)) {
            $this->apply(new UserAdded($this->uuid, $userId));
        }
    }


    public function removeUser(
        StringLiteral $userId
    ) {
        if (in_array($userId, $this->userIds)) {
            $this->apply(new UserRemoved($this->uuid, $userId));
        }
    }

    /**
     * Delete a role.
     *
     */
    public function delete(
        UUID $uuid
    ) {
        $this->apply(new RoleDeleted($uuid));
    }


    public function applyRoleCreated(RoleCreated $roleCreated)
    {
        $this->uuid = $roleCreated->getUuid();
        $this->name = $roleCreated->getName();
    }


    public function applyRoleRenamed(RoleRenamed $roleRenamed)
    {
        $this->name = $roleRenamed->getName();
    }


    public function applyConstraintAdded(ConstraintAdded $constraintAdded)
    {
        $this->queries[$constraintAdded->getSapiVersion()->toNative()] = $constraintAdded->getQuery();
    }


    public function applyConstraintUpdated(ConstraintUpdated $constraintUpdated)
    {
        $this->queries[$constraintUpdated->getSapiVersion()->toNative()] = $constraintUpdated->getQuery();
    }


    public function applyConstraintRemoved(ConstraintRemoved $constraintRemoved)
    {
        unset($this->queries[$constraintRemoved->getSapiVersion()->toNative()]);
    }


    public function applyPermissionAdded(PermissionAdded $permissionAdded)
    {
        $permission = $permissionAdded->getPermission();

        $this->permissions[$permission->getName()] = $permission;
    }


    public function applyPermissionRemoved(PermissionRemoved $permissionRemoved)
    {
        $permission = $permissionRemoved->getPermission();

        unset($this->permissions[$permission->getName()]);
    }


    public function applyLabelAdded(LabelAdded $labelAdded)
    {
        $labelId = $labelAdded->getLabelId();
        $this->labelIds[] = $labelId;
    }


    public function applyLabelRemoved(LabelRemoved $labelRemoved)
    {
        $labelId = $labelRemoved->getLabelId();
        $this->labelIds = array_diff($this->labelIds, [$labelId]);
    }


    public function applyUserAdded(UserAdded $userAdded)
    {
        $userId = $userAdded->getUserId();
        $this->userIds[] = $userId;
    }


    public function applyUserRemoved(UserRemoved $userRemoved)
    {
        $userId = $userRemoved->getUserId();

        if (($index = array_search($userId, $this->userIds)) !== false) {
            unset($this->userIds[$index]);
        }
    }
}
