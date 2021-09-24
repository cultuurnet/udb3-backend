<?php

declare(strict_types=1);

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
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

class Role extends EventSourcedAggregateRoot
{
    private UUID $uuid;

    private StringLiteral $name;

    /**
     * @var Query[]
     */
    private array $queries = [];

    /**
     * @var Permission[]
     */
    private array $permissions = [];

    /**
     * @var UUID[]
     */
    private array $labelIds = [];

    /**
     * @var StringLiteral[]
     */
    private array $userIds = [];

    public function getAggregateRootId(): string
    {
        return $this->uuid->toNative();
    }

    public static function create(
        UUID $uuid,
        StringLiteral $name
    ): Role {
        $role = new Role();

        $role->apply(new RoleCreated(
            $uuid,
            $name
        ));

        return $role;
    }

    public function rename(
        UUID $uuid,
        StringLiteral $name
    ): void {
        $this->apply(new RoleRenamed($uuid, $name));
    }

    public function addConstraint(Query $query): void
    {
        if ($this->queryEmpty()) {
            $this->apply(new ConstraintAdded($this->uuid, $query));
        }
    }

    public function updateConstraint(Query $query): void
    {
        if (!$this->queryEmpty() && !$this->querySameValue($query)) {
            $this->apply(new ConstraintUpdated($this->uuid, $query));
        }
    }

    public function removeConstraint(): void
    {
        if (!$this->queryEmpty()) {
            $this->apply(new ConstraintRemoved($this->uuid));
        }
    }

    private function queryEmpty(): bool
    {
        return empty($this->queries['v3']);
    }

    private function querySameValue(Query $query): bool
    {
        return $this->queries['v3']->sameValueAs($query);
    }

    public function addPermission(
        UUID $uuid,
        Permission $permission
    ): void {
        if (!in_array($permission, $this->permissions)) {
            $this->apply(new PermissionAdded($uuid, $permission));
        }
    }

    public function removePermission(
        UUID $uuid,
        Permission $permission
    ): void {
        if (in_array($permission, $this->permissions)) {
            $this->apply(new PermissionRemoved($uuid, $permission));
        }
    }

    public function addLabel(
        UUID $labelId
    ): void {
        if (!in_array($labelId, $this->labelIds)) {
            $this->apply(new LabelAdded($this->uuid, $labelId));
        }
    }

    public function removeLabel(
        UUID $labelId
    ): void {
        if (in_array($labelId, $this->labelIds)) {
            $this->apply(new LabelRemoved($this->uuid, $labelId));
        }
    }

    public function addUser(
        StringLiteral $userId
    ): void {
        if (!in_array($userId, $this->userIds)) {
            $this->apply(new UserAdded($this->uuid, $userId));
        }
    }

    public function removeUser(
        StringLiteral $userId
    ): void {
        if (in_array($userId, $this->userIds)) {
            $this->apply(new UserRemoved($this->uuid, $userId));
        }
    }

    public function delete(
        UUID $uuid
    ): void {
        $this->apply(new RoleDeleted($uuid));
    }

    public function applyRoleCreated(RoleCreated $roleCreated): void
    {
        $this->uuid = $roleCreated->getUuid();
        $this->name = $roleCreated->getName();
    }

    public function applyRoleRenamed(RoleRenamed $roleRenamed): void
    {
        $this->name = $roleRenamed->getName();
    }

    public function applyConstraintAdded(ConstraintAdded $constraintAdded): void
    {
        $this->queries['v3'] = $constraintAdded->getQuery();
    }

    public function applyConstraintUpdated(ConstraintUpdated $constraintUpdated): void
    {
        $this->queries['v3'] = $constraintUpdated->getQuery();
    }

    public function applyConstraintRemoved(ConstraintRemoved $constraintRemoved): void
    {
        unset($this->queries['v3']);
    }

    public function applyPermissionAdded(PermissionAdded $permissionAdded): void
    {
        $permission = $permissionAdded->getPermission();

        $this->permissions[$permission->getName()] = $permission;
    }

    public function applyPermissionRemoved(PermissionRemoved $permissionRemoved): void
    {
        $permission = $permissionRemoved->getPermission();

        unset($this->permissions[$permission->getName()]);
    }

    public function applyLabelAdded(LabelAdded $labelAdded): void
    {
        $labelId = $labelAdded->getLabelId();
        $this->labelIds[] = $labelId;
    }

    public function applyLabelRemoved(LabelRemoved $labelRemoved): void
    {
        $labelId = $labelRemoved->getLabelId();
        $this->labelIds = array_diff($this->labelIds, [$labelId]);
    }

    public function applyUserAdded(UserAdded $userAdded): void
    {
        $userId = $userAdded->getUserId();
        $this->userIds[] = $userId;
    }

    public function applyUserRemoved(UserRemoved $userRemoved): void
    {
        $userId = $userRemoved->getUserId();

        if (($index = array_search($userId, $this->userIds)) !== false) {
            unset($this->userIds[$index]);
        }
    }
}
