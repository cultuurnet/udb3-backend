<?php

namespace CultuurNet\UDB3\Role\Services;

use Broadway\CommandHandling\CommandBusInterface;
use Broadway\Repository\RepositoryInterface;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\Role\Commands\AddConstraint;
use CultuurNet\UDB3\Role\Commands\AddLabel;
use CultuurNet\UDB3\Role\Commands\AddPermission;
use CultuurNet\UDB3\Role\Commands\AddUser;
use CultuurNet\UDB3\Role\Commands\DeleteRole;
use CultuurNet\UDB3\Role\Commands\RemoveConstraint;
use CultuurNet\UDB3\Role\Commands\RemoveLabel;
use CultuurNet\UDB3\Role\Commands\RemovePermission;
use CultuurNet\UDB3\Role\Commands\RemoveUser;
use CultuurNet\UDB3\Role\Commands\RenameRole;
use CultuurNet\UDB3\Role\Commands\UpdateConstraint;
use CultuurNet\UDB3\Role\Role;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Role\ValueObjects\Query;
use CultuurNet\UDB3\ValueObject\SapiVersion;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

class DefaultRoleEditingService implements RoleEditingServiceInterface
{
    /**
     * @var CommandBusInterface
     */
    private $commandBus;

    /**
     * @var UuidGeneratorInterface
     */
    private $uuidGenerator;

    /**
     * @var RepositoryInterface
     */
    private $writeRepository;

    public function __construct(
        CommandBusInterface $commandBus,
        UuidGeneratorInterface $uuidGenerator,
        RepositoryInterface $writeRepository
    ) {
        $this->commandBus = $commandBus;
        $this->uuidGenerator = $uuidGenerator;
        $this->writeRepository = $writeRepository;
    }

    public function create(StringLiteral $name): UUID
    {
        $uuid = new UUID($this->uuidGenerator->generate());

        $role = Role::create($uuid, $name);

        $this->writeRepository->save($role);

        return $uuid;
    }

    public function rename(UUID $uuid, StringLiteral $name): void
    {
        $command = new RenameRole(
            $uuid,
            $name
        );

        $this->commandBus->dispatch($command);
    }

    public function addPermission(UUID $uuid, Permission $permission): void
    {
        $command = new AddPermission(
            $uuid,
            $permission
        );

        $this->commandBus->dispatch($command);
    }

    public function removePermission(UUID $uuid, Permission $permission): void
    {
        $command = new RemovePermission(
            $uuid,
            $permission
        );

        $this->commandBus->dispatch($command);
    }

    public function addUser(UUID $uuid, StringLiteral $userId): void
    {
        $command = new AddUser(
            $uuid,
            $userId
        );

        $this->commandBus->dispatch($command);
    }

    public function removeUser(UUID $uuid, StringLiteral $userId): void
    {
        $command = new RemoveUser(
            $uuid,
            $userId
        );

        $this->commandBus->dispatch($command);
    }

    public function addConstraint(UUID $uuid, SapiVersion $sapiVersion, Query $query): void
    {
        $command = new AddConstraint(
            $uuid,
            $sapiVersion,
            $query
        );

        $this->commandBus->dispatch($command);
    }

    public function updateConstraint(UUID $uuid, SapiVersion $sapiVersion, Query $query): void
    {
        $command = new UpdateConstraint(
            $uuid,
            $sapiVersion,
            $query
        );

        $this->commandBus->dispatch($command);
    }

    public function removeConstraint(UUID $uuid, SapiVersion $sapiVersion): void
    {
        $command = new RemoveConstraint(
            $uuid,
            $sapiVersion
        );

        $this->commandBus->dispatch($command);
    }

    public function addLabel(UUID $uuid, UUID $labelId): void
    {
        $command = new AddLabel(
            $uuid,
            $labelId
        );

        $this->commandBus->dispatch($command);
    }

    public function removeLabel(UUID $uuid, UUID $labelId): void
    {
        $command = new RemoveLabel(
            $uuid,
            $labelId
        );

        $this->commandBus->dispatch($command);
    }

    public function delete(UUID $uuid): void
    {
        $command = new DeleteRole(
            $uuid
        );

        $this->commandBus->dispatch($command);
    }
}
