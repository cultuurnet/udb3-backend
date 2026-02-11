<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership\Readmodels;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\CommandHandling\AuthorizedCommandBusInterface;
use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;
use CultuurNet\UDB3\Http\Ownership\Search\SearchParameter;
use CultuurNet\UDB3\Http\Ownership\Search\SearchQuery;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Ownership\Events\OwnershipApproved;
use CultuurNet\UDB3\Ownership\Events\OwnershipDeleted;
use CultuurNet\UDB3\Ownership\Readmodels\Name\ItemNameResolver;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItem;
use CultuurNet\UDB3\Ownership\Repositories\Search\OwnershipSearchRepository;
use CultuurNet\UDB3\Role\Commands\AddConstraint;
use CultuurNet\UDB3\Role\Commands\AddPermission;
use CultuurNet\UDB3\Role\Commands\AddUser;
use CultuurNet\UDB3\Role\Commands\CreateRole;
use CultuurNet\UDB3\Role\Commands\DeleteRole;
use CultuurNet\UDB3\Role\Commands\RemoveUser;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Role\ValueObjects\Query;
use CultuurNet\UDB3\Model\ValueObject\Identity\UuidFactory\UuidFactory;

final class OwnershipPermissionProjector implements EventListener
{
    use DelegateEventHandlingToSpecificMethodTrait {
        DelegateEventHandlingToSpecificMethodTrait::handle as handleMethodSpecificEvents;
    }

    private AuthorizedCommandBusInterface $commandBus;
    private OwnershipSearchRepository $ownershipSearchRepository;
    private UuidFactory $uuidFactory;
    private ItemNameResolver $itemNameResolver;

    public function __construct(
        AuthorizedCommandBusInterface $commandBus,
        OwnershipSearchRepository $ownershipSearchRepository,
        UuidFactory $uuidFactory,
        ItemNameResolver $itemNameResolver
    ) {
        $this->commandBus = $commandBus;
        $this->ownershipSearchRepository = $ownershipSearchRepository;
        $this->uuidFactory = $uuidFactory;
        $this->itemNameResolver = $itemNameResolver;
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

    public function deleteRoleIfNoOwnerships(?Uuid $roleId): void
    {
        if (!$this->hasOtherOwnershipsForRole($roleId)) {
            $this->commandBus->dispatch(
                new DeleteRole($roleId)
            );
        }
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
        } else {
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

        $roleId = $ownershipItem->getRoleId();

        $this->commandBus->dispatch(
            new RemoveUser(
                $roleId,
                $ownershipItem->getOwnerId()
            )
        );
        $this->ownershipSearchRepository->updateRoleId($ownershipItem->getId(), null);

        // Auto-delete the role if there are no remaining ownerships for it
        $this->deleteRoleIfNoOwnerships($roleId);

        $this->commandBus->enableAuthorization();
    }

    private function hasOtherOwnershipsForRole(Uuid $roleId): bool
    {
        // Search for any ownerships (approved state only) that are linked to this role
        // We look for approved ownerships since only approved ownerships have members in the role
        $ownerships = $this->ownershipSearchRepository->search(
            new SearchQuery(
                [
                    new SearchParameter('state', 'approved'),
                ],
                0,
                100
            )
        );

        $roleIdString = $roleId->toString();
        foreach ($ownerships as $ownership) {
            if ($ownership->getRoleId() && $ownership->getRoleId()->toString() === $roleIdString) {
                return true;
            }
        }

        return false;
    }

    private function getExistingRoleId(string $itemId): ?Uuid
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

        return $existingOwnerships->getFirst()->getRoleId();
    }

    private function createRole(OwnershipItem $ownershipItem): Uuid
    {
        $roleId = new Uuid($this->uuidFactory->uuid4()->toString());

        $this->commandBus->dispatch(
            new CreateRole(
                $roleId,
                'Beheerders organisatie ' . $this->itemNameResolver->resolve($ownershipItem->getItemId())
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
}
