<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership\Readmodels;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Ownership\Events\OwnershipApproved;
use CultuurNet\UDB3\Ownership\Events\OwnershipDeleted;
use CultuurNet\UDB3\Ownership\Events\OwnershipRejected;
use CultuurNet\UDB3\Ownership\Events\OwnershipRequested;
use CultuurNet\UDB3\Ownership\OwnershipState;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItem;
use CultuurNet\UDB3\Ownership\Repositories\Search\OwnershipSearchRepository;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\Role\Events\ConstraintAdded;
use CultuurNet\UDB3\Role\Events\ConstraintRemoved;
use CultuurNet\UDB3\Role\Events\ConstraintUpdated;
use CultuurNet\UDB3\Role\ReadModel\Search\SearchByRoleIdAndPermissions;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Role\ValueObjects\Query;
use Doctrine\DBAL\Connection;

final class OwnershipSearchProjector implements EventListener
{
    use DelegateEventHandlingToSpecificMethodTrait {
        DelegateEventHandlingToSpecificMethodTrait::handle as handleMethodSpecificEvents;
    }

    private OwnershipSearchRepository $ownershipSearchRepository;
    private DocumentRepository $organizerRepository;
    private SearchByRoleIdAndPermissions $searchByRoleIdAndPermissions;
    private Connection $connection;

    public function __construct(OwnershipSearchRepository $ownershipSearchRepository, DocumentRepository $organizerRepository, SearchByRoleIdAndPermissions $searchByRoleIdAndPermissions, Connection $connection)
    {
        $this->ownershipSearchRepository = $ownershipSearchRepository;
        $this->organizerRepository = $organizerRepository;
        $this->searchByRoleIdAndPermissions = $searchByRoleIdAndPermissions;
        $this->connection = $connection;
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

    public function applyOwnershipRequested(OwnershipRequested $ownershipRequested): void
    {
        $ownershipItem = new OwnershipItem(
            $ownershipRequested->getId(),
            $ownershipRequested->getItemId(),
            $ownershipRequested->getItemType(),
            $ownershipRequested->getOwnerId(),
            OwnershipState::requested()->toString()
        );

        $this->ownershipSearchRepository->save($ownershipItem);
    }

    public function applyOwnershipApproved(OwnershipApproved $ownershipApproved): void
    {
        $this->ownershipSearchRepository->updateState(
            $ownershipApproved->getId(),
            OwnershipState::approved()
        );
    }

    public function applyOwnershipRejected(OwnershipRejected $ownershipRejected): void
    {
        $this->ownershipSearchRepository->updateState(
            $ownershipRejected->getId(),
            OwnershipState::rejected()
        );
    }

    public function applyOwnershipDeleted(OwnershipDeleted $ownershipDeleted): void
    {
        $this->ownershipSearchRepository->updateState(
            $ownershipDeleted->getId(),
            OwnershipState::deleted()
        );
    }

    protected function applyConstraintAdded(ConstraintAdded $constraintEvent): void
    {
        $this->processConstraint($constraintEvent->getUuid(), $constraintEvent->getQuery());
    }

    protected function applyConstraintUpdated(ConstraintUpdated $constraintEvent): void
    {
        $roleId = $constraintEvent->getUuid();

        $this->connection->beginTransaction();

        // First clear all existing owners for this role, because if the constraint is changed some users might no longer be valid.
        $this->ownershipSearchRepository->deleteByRole($roleId);

        $this->processConstraint($roleId, $constraintEvent->getQuery());

        $this->connection->commit();
    }

    protected function applyConstraintRemoved(ConstraintRemoved $constraintEvent): void
    {
        $this->ownershipSearchRepository->deleteByRole($constraintEvent->getUuid());
    }

    private function processConstraint(Uuid $roleId, Query $query): void
    {
        $organizerId = $this->extractUuid($query);

        if ($organizerId === null) {
            return;
        }

        try {
            $this->organizerRepository->fetch($organizerId);
        } catch (DocumentDoesNotExist $e) {
            // This uuid does not belong to an organizer, so we don't have to save them into the ownership_search table
            return;
        }

        $users = $this->searchByRoleIdAndPermissions->findAllUsers($roleId, [Permission::organisatiesBewerken()->toString()]);

        foreach ($users as $userId) {
            if ($this->ownershipSearchRepository->doesUserForOrganisationExist(new Uuid($organizerId), $userId)) {
                continue;
            }

            $ownershipItem = new OwnershipItem(
                Uuid::uuid4()->toString(),
                $organizerId,
                'organizer',
                $userId,
                OwnershipState::approved()->toString()
            );

            $this->ownershipSearchRepository->save(
                $ownershipItem->withRoleId($roleId)
            );
        }
    }

    private function extractUuid(Query $query): ?string
    {
        preg_match('/id:([a-f0-9\-]{36})/', $query->toString(), $matches);
        return $matches[1] ?? null;
    }
}
