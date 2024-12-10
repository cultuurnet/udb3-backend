<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role;

use Broadway\EventSourcing\EventSourcedAggregateRoot;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
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

class Role extends EventSourcedAggregateRoot
{
    private Uuid $uuid;

    private string $name;

    private ?Query $query = null;

    /**
     * @var Permission[]
     */
    private array $permissions = [];

    /**
     * @var Uuid[]
     */
    private array $labelIds = [];

    /**
     * @var string[]
     */
    private array $userIds = [];

    public function getAggregateRootId(): string
    {
        return $this->uuid->toString();
    }

    public static function create(
        Uuid $uuid,
        string $name
    ): Role {
        $role = new Role();

        $role->apply(new RoleCreated(
            $uuid,
            $name
        ));

        return $role;
    }

    public function rename(
        Uuid $uuid,
        string $name
    ): void {
        $this->apply(new RoleRenamed($uuid, $name));
    }

    public function addConstraint(Query $query): void
    {
        if ($this->isCurrentQueryEmpty() && !$query->isEmpty()) {
            $this->apply(new ConstraintAdded($this->uuid, $query));
        }
    }

    public function updateConstraint(Query $query): void
    {
        if (!$this->isCurrentQueryEmpty() && !$this->query->sameAs($query)) {
            $this->apply(new ConstraintUpdated($this->uuid, $query));
        }
    }

    public function removeConstraint(): void
    {
        if (!$this->isCurrentQueryEmpty()) {
            $this->apply(new ConstraintRemoved($this->uuid));
        }
    }

    private function isCurrentQueryEmpty(): bool
    {
        if ($this->query === null) {
            return true;
        }

        return $this->query->isEmpty();
    }

    public function addPermission(
        Uuid $uuid,
        Permission $permission
    ): void {
        if (!in_array($permission, $this->permissions)) {
            $this->apply(new PermissionAdded($uuid, $permission));
        }
    }

    public function removePermission(
        Uuid $uuid,
        Permission $permission
    ): void {
        if (in_array($permission, $this->permissions)) {
            $this->apply(new PermissionRemoved($uuid, $permission));
        }
    }

    public function addLabel(
        Uuid $labelId
    ): void {
        if (!in_array($labelId, $this->labelIds)) {
            $this->apply(new LabelAdded($this->uuid, $labelId));
        }
    }

    public function removeLabel(
        Uuid $labelId
    ): void {
        if (in_array($labelId, $this->labelIds)) {
            $this->apply(new LabelRemoved($this->uuid, $labelId));
        }
    }

    public function addUser(
        string $userId
    ): void {
        if (!in_array($userId, $this->userIds)) {
            $this->apply(new UserAdded($this->uuid, $userId));
        }
    }

    public function removeUser(
        string $userId
    ): void {
        if (in_array($userId, $this->userIds)) {
            $this->apply(new UserRemoved($this->uuid, $userId));
        }
    }

    public function delete(
        Uuid $uuid
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
        if (!empty($roleRenamed->getName()) && $this->name !== $roleRenamed->getName()) {
            $this->name = $roleRenamed->getName();
        }
    }

    public function applyConstraintAdded(ConstraintAdded $constraintAdded): void
    {
        $this->query = $constraintAdded->getQuery();
    }

    public function applyConstraintUpdated(ConstraintUpdated $constraintUpdated): void
    {
        $this->query = $constraintUpdated->getQuery();
    }

    public function applyConstraintRemoved(ConstraintRemoved $constraintRemoved): void
    {
        $this->query = null;
    }

    public function applyPermissionAdded(PermissionAdded $permissionAdded): void
    {
        $permission = $permissionAdded->getPermission();

        $this->permissions[$permission->toString()] = $permission;
    }

    public function applyPermissionRemoved(PermissionRemoved $permissionRemoved): void
    {
        $permission = $permissionRemoved->getPermission();

        unset($this->permissions[$permission->toString()]);
    }

    public function applyLabelAdded(LabelAdded $labelAdded): void
    {
        $labelId = $labelAdded->getLabelId();
        $this->labelIds[] = $labelId;
    }

    public function applyLabelRemoved(LabelRemoved $labelRemoved): void
    {
        $labelId = $labelRemoved->getLabelId();
        if (($index = array_search($labelId, $this->labelIds)) !== false) {
            unset($this->labelIds[$index]);
        }
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
