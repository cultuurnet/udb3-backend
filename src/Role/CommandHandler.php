<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role;

use Broadway\Repository\Repository;
use CultuurNet\UDB3\CommandHandling\Udb3CommandHandler as AbstractCommandHandler;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Role\Commands\AddConstraint;
use CultuurNet\UDB3\Role\Commands\AddLabel;
use CultuurNet\UDB3\Role\Commands\AddPermission;
use CultuurNet\UDB3\Role\Commands\AddUser;
use CultuurNet\UDB3\Role\Commands\CreateRole;
use CultuurNet\UDB3\Role\Commands\DeleteRole;
use CultuurNet\UDB3\Role\Commands\RemoveConstraint;
use CultuurNet\UDB3\Role\Commands\RemoveLabel;
use CultuurNet\UDB3\Role\Commands\RemovePermission;
use CultuurNet\UDB3\Role\Commands\RemoveUser;
use CultuurNet\UDB3\Role\Commands\RenameRole;
use CultuurNet\UDB3\Role\Commands\UpdateConstraint;

class CommandHandler extends AbstractCommandHandler
{
    private Repository $repository;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    public function handleCreateRole(CreateRole $createRole): void
    {
        $role = Role::create(
            $createRole->getUuid(),
            $createRole->getName()
        );

        $this->save($role);
    }

    public function handleRenameRole(RenameRole $renameRole): void
    {
        $role = $this->load($renameRole->getUuid());

        $role->rename(
            $renameRole->getUuid(),
            $renameRole->getName()
        );

        $this->save($role);
    }

    public function handleAddConstraint(AddConstraint $addConstraint): void
    {
        $role = $this->load($addConstraint->getUuid());

        $role->addConstraint($addConstraint->getQuery());

        $this->save($role);
    }

    public function handleUpdateConstraint(UpdateConstraint $updateConstraint): void
    {
        $role = $this->load($updateConstraint->getUuid());

        $role->updateConstraint($updateConstraint->getQuery());

        $this->save($role);
    }

    public function handleRemoveConstraint(RemoveConstraint $removeConstraint): void
    {
        $role = $this->load($removeConstraint->getUuid());

        $role->removeConstraint();

        $this->save($role);
    }

    public function handleAddPermission(AddPermission $addPermission): void
    {
        $role = $this->load($addPermission->getUuid());

        $role->addPermission(
            $addPermission->getUuid(),
            $addPermission->getRolePermission()
        );

        $this->save($role);
    }

    public function handleRemovePermission(RemovePermission $removePermission): void
    {
        $role = $this->load($removePermission->getUuid());

        $role->removePermission(
            $removePermission->getUuid(),
            $removePermission->getRolePermission()
        );

        $this->save($role);
    }

    public function handleAddUser(AddUser $addUser): void
    {
        $role = $this->load($addUser->getUuid());

        $role->addUser(
            $addUser->getUserId()
        );

        $this->save($role);
    }

    public function handleRemoveUser(RemoveUser $removeUser): void
    {
        $role = $this->load($removeUser->getUuid());

        $role->removeUser(
            $removeUser->getUserId()
        );

        $this->save($role);
    }

    public function handleDeleteRole(DeleteRole $deleteRole): void
    {
        $role = $this->load($deleteRole->getUuid());

        //@TODO Check linked users and labels once added.

        $role->delete($deleteRole->getUuid());

        $this->save($role);
    }

    public function handleAddLabel(AddLabel $addLabel): void
    {
        $role = $this->load($addLabel->getUuid());

        $role->addLabel(
            $addLabel->getLabelId()
        );

        $this->save($role);
    }

    public function handleRemoveLabel(RemoveLabel $removeLabel): void
    {
        $role = $this->load($removeLabel->getUuid());

        $role->removeLabel(
            $removeLabel->getLabelId()
        );

        $this->save($role);
    }

    private function load(UUID $uuid): Role
    {
        /** @var Role $role */
        $role = $this->repository->load($uuid->toString());
        return $role;
    }

    private function save(Role $role): void
    {
        $this->repository->save($role);
    }
}
