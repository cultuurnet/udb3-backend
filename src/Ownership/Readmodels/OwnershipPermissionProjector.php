<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership\Readmodels;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\CommandHandling\AuthorizedCommandBusInterface;
use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;
use CultuurNet\UDB3\Http\Ownership\Search\SearchParameter;
use CultuurNet\UDB3\Http\Ownership\Search\SearchQuery;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Ownership\Events\OwnershipApproved;
use CultuurNet\UDB3\Ownership\Events\OwnershipDeleted;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItem;
use CultuurNet\UDB3\Ownership\Repositories\Search\OwnershipSearchRepository;
use CultuurNet\UDB3\Role\Commands\AddConstraint;
use CultuurNet\UDB3\Role\Commands\AddPermission;
use CultuurNet\UDB3\Role\Commands\AddUser;
use CultuurNet\UDB3\Role\Commands\CreateRole;
use CultuurNet\UDB3\Role\Commands\RemoveUser;
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

    public function __construct(
        AuthorizedCommandBusInterface $commandBus,
        OwnershipSearchRepository $ownershipSearchRepository,
        UuidFactoryInterface $uuidFactory
    ) {
        $this->commandBus = $commandBus;
        $this->ownershipSearchRepository = $ownershipSearchRepository;
        $this->uuidFactory = $uuidFactory;
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
        $this->commandBus->disableAuthorization();

        $ownershipItem = $this->ownershipSearchRepository->getById($ownershipApproved->getId());
        $roleId = $this->getExistingRoleId($ownershipItem->getItemId());

        if ($roleId !== null) {
            $this->commandBus->dispatch(
                new AddUser(
                    $roleId,
                    $ownershipItem->getOwnerId()
                )
            );
        }

        if ($roleId === null) {
            $roleId = $this->createRole($ownershipItem);

            $this->commandBus->dispatch(
                new AddConstraint(
                    $roleId,
                    new Query('(id:' . $ownershipItem->getItemId() . ' OR (organizer.id:' . $ownershipItem->getItemId() . ' AND _type:event))')
                )
            );

            $this->commandBus->dispatch(
                new AddPermission(
                    $roleId,
                    Permission::organisatiesBewerken()
                )
            );

            $this->commandBus->dispatch(
                new AddPermission(
                    $roleId,
                    Permission::aanbodBewerken()
                )
            );
        }

        $this->ownershipSearchRepository->updateRoleId($ownershipItem->getId(), $roleId);

        $this->commandBus->enableAuthorization();
    }

    protected function applyOwnershipDeleted(OwnershipDeleted $ownershipDeleted): void
    {
        $ownershipItem = $this->ownershipSearchRepository->getById($ownershipDeleted->getId());

        if ($ownershipItem->getRoleId() === null) {
            return;
        }

        $this->commandBus->disableAuthorization();

        $this->commandBus->dispatch(
            new RemoveUser(
                $ownershipItem->getRoleId(),
                $ownershipItem->getOwnerId()
            )
        );
        $this->ownershipSearchRepository->updateRoleId($ownershipItem->getId(), null);

        $this->commandBus->enableAuthorization();
    }

    private function getExistingRoleId(string $itemId): ?UUID
    {
        $existingOwnerships = $this->ownershipSearchRepository->search(
            new SearchQuery(
                [
                    new SearchParameter('itemId', $itemId),
                ],
                0,
                1
            )
        );

        if ($existingOwnerships->count() < 1) {
            return null;
        }

        if ($existingOwnerships->getFirst()->getRoleId() === null) {
            return null;
        }

        return $existingOwnerships->getFirst()->getRoleId();
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
