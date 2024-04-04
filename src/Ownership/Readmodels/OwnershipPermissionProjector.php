<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership\Readmodels;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\CommandHandling\AuthorizedCommandBusInterface;
use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Ownership\Events\OwnershipApproved;
use CultuurNet\UDB3\Ownership\Events\OwnershipDeleted;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItem;
use CultuurNet\UDB3\Ownership\Repositories\Search\OwnershipSearchRepository;
use CultuurNet\UDB3\Role\Commands\AddConstraint;
use CultuurNet\UDB3\Role\Commands\AddPermission;
use CultuurNet\UDB3\Role\Commands\AddUser;
use CultuurNet\UDB3\Role\Commands\CreateRole;
use CultuurNet\UDB3\Role\Commands\DeleteRole;
use CultuurNet\UDB3\Role\ReadModel\Search\RepositoryInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Role\ValueObjects\Query;
use Ramsey\Uuid\UuidFactoryInterface;

final class OwnershipPermissionProjector implements EventListener
{
    use DelegateEventHandlingToSpecificMethodTrait {
        DelegateEventHandlingToSpecificMethodTrait::handle as handleMethodSpecificEvents;
    }

    private AuthorizedCommandBusInterface $commandBus;
    private OwnershipSearchRepository $ownershipSearchRepository;
    private UuidFactoryInterface $uuidFactory;
    private RepositoryInterface $roleSearchRepository;

    public function __construct(
        AuthorizedCommandBusInterface $commandBus,
        OwnershipSearchRepository $ownershipSearchRepository,
        UuidFactoryInterface $uuidFactory,
        RepositoryInterface $roleSearchRepository
    ) {
        $this->commandBus = $commandBus;
        $this->ownershipSearchRepository = $ownershipSearchRepository;
        $this->uuidFactory = $uuidFactory;
        $this->roleSearchRepository = $roleSearchRepository;
    }

    public function handle(DomainMessage $domainMessage): void
    {
        $event = $domainMessage->getPayload();

        $handleMethod = $this->getHandleMethodName($event);
        if (!$handleMethod) {
            return;
        }

        $this->{$handleMethod}($event, $domainMessage);
    }

    protected function applyOwnershipApproved(OwnershipApproved $ownershipApproved): void
    {
        $ownershipItem = $this->ownershipSearchRepository->getById($ownershipApproved->getId());

        $this->commandBus->disableAuthorization();

        $this->createOrganizationRole($ownershipItem);
        $this->createEventRole($ownershipItem);

        $this->commandBus->enableAuthorization();
    }

    protected function applyOwnershipDeleted(OwnershipDeleted $ownershipDeleted): void
    {
        $this->commandBus->disableAuthorization();

        $roles = $this->roleSearchRepository->search(
            $this->createRoleName($ownershipDeleted->getId())
        );

        foreach ($roles->getMember() as $role) {
            $this->commandBus->dispatch(
                new DeleteRole(new UUID($role['uuid']))
            );
        }

        $this->commandBus->enableAuthorization();
    }

    private function createOrganizationRole(OwnershipItem $ownershipItem): void
    {
        $roleId = $this->createRole($ownershipItem);

        $this->commandBus->dispatch(
            new AddConstraint(
                $roleId,
                new Query('id:' . $ownershipItem->getItemId())
            )
        );

        $this->commandBus->dispatch(
            new AddPermission(
                $roleId,
                Permission::organisatiesBewerken()
            )
        );
    }

    private function createEventRole(OwnershipItem $ownershipItem): void
    {
        $roleId = $this->createRole($ownershipItem);

        $this->commandBus->dispatch(
            new AddConstraint(
                $roleId,
                new Query('organizer.id:' . $ownershipItem->getItemId())
            )
        );

        $this->commandBus->dispatch(
            new AddPermission(
                $roleId,
                Permission::aanbodBewerken()
            )
        );
    }

    private function createRole(OwnershipItem $ownershipItem): UUID
    {
        $roleId = new UUID($this->uuidFactory->uuid4()->toString());

        $this->commandBus->dispatch(
            new CreateRole(
                $roleId,
                $this->createRoleName($ownershipItem->getId())
            )
        );

        $this->commandBus->dispatch(
            new AddUser(
                $roleId,
                $ownershipItem->getOwnerId()
            )
        );

        return $roleId;
    }

    private function createRoleName(string $ownershipId): string
    {
        return 'Ownership ' . $ownershipId;
    }
}
