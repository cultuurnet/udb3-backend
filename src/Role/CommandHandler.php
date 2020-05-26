<?php

namespace CultuurNet\UDB3\Role;

use Broadway\Repository\RepositoryInterface;
use CultuurNet\UDB3\CommandHandling\Udb3CommandHandler as AbstractCommandHandler;
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
use ValueObjects\Identity\UUID;

class CommandHandler extends AbstractCommandHandler
{
    /**
     * @var RepositoryInterface
     */
    private $repository;

    /**
     * CommandHandler constructor.
     * @param RepositoryInterface $repository
     */
    public function __construct(RepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param CreateRole $createRole
     */
    public function handleCreateRole(CreateRole $createRole)
    {
        $role = Role::create(
            $createRole->getUuid(),
            $createRole->getName()
        );

        $this->save($role);
    }

    /**
     * @param RenameRole $renameRole
     */
    public function handleRenameRole(RenameRole $renameRole)
    {
        $role = $this->load($renameRole->getUuid());

        $role->rename(
            $renameRole->getUuid(),
            $renameRole->getName()
        );

        $this->save($role);
    }

    /**
     * @param AddConstraint $addConstraint
     */
    public function handleAddConstraint(AddConstraint $addConstraint): void
    {
        $role = $this->load($addConstraint->getUuid());

        $role->addConstraint(
            $addConstraint->getSapiVersion(),
            $addConstraint->getQuery()
        );

        $this->save($role);
    }

    /**
     * @param UpdateConstraint $updateConstraint
     */
    public function handleUpdateConstraint(UpdateConstraint $updateConstraint): void
    {
        $role = $this->load($updateConstraint->getUuid());

        $role->updateConstraint(
            $updateConstraint->getSapiVersion(),
            $updateConstraint->getQuery()
        );

        $this->save($role);
    }

    /**
     * @param RemoveConstraint $removeConstraint
     */
    public function handleRemoveConstraint(RemoveConstraint $removeConstraint): void
    {
        $role = $this->load($removeConstraint->getUuid());

        $role->removeConstraint(
            $removeConstraint->getSapiVersion()
        );

        $this->save($role);
    }

    /**
     * @param AddPermission $addPermission
     */
    public function handleAddPermission(AddPermission $addPermission)
    {
        $role = $this->load($addPermission->getUuid());

        $role->addPermission(
            $addPermission->getUuid(),
            $addPermission->getRolePermission()
        );

        $this->save($role);
    }

    /**
     * @param RemovePermission $removePermission
     */
    public function handleRemovePermission(RemovePermission $removePermission)
    {
        $role = $this->load($removePermission->getUuid());

        $role->removePermission(
            $removePermission->getUuid(),
            $removePermission->getRolePermission()
        );

        $this->save($role);
    }

    /**
     * @param AddUser $addUser
     */
    public function handleAddUser(AddUser $addUser)
    {
        $role = $this->load($addUser->getUuid());

        $role->addUser(
            $addUser->getUserId()
        );

        $this->save($role);
    }

    /**
     * @param RemoveUser $removeUser
     */
    public function handleRemoveUser(RemoveUser $removeUser)
    {
        $role = $this->load($removeUser->getUuid());

        $role->removeUser(
            $removeUser->getUserId()
        );

        $this->save($role);
    }

    /**
     * @param DeleteRole $deleteRole
     */
    public function handleDeleteRole(DeleteRole $deleteRole)
    {
        $role = $this->load($deleteRole->getUuid());

        //@TODO Check linked users and labels once added.

        $role->delete($deleteRole->getUuid());

        $this->save($role);
    }

    /**
     * @param AddLabel $addLabel
     */
    public function handleAddLabel(AddLabel $addLabel)
    {
        $role = $this->load($addLabel->getUuid());

        $role->addLabel(
            $addLabel->getLabelId()
        );

        $this->save($role);
    }

    /**
     * @param RemoveLabel $removeLabel
     */
    public function handleRemoveLabel(RemoveLabel $removeLabel)
    {
        $role = $this->load($removeLabel->getUuid());

        $role->removeLabel(
            $removeLabel->getLabelId()
        );

        $this->save($role);
    }

    /**
     * @param UUID $uuid
     * @return Role
     */
    private function load(UUID $uuid)
    {
        /** @var Role $role */
        $role = $this->repository->load($uuid);
        return $role;
    }

    /**
     * @param Role $role
     */
    private function save(Role $role)
    {
        $this->repository->save($role);
    }
}
