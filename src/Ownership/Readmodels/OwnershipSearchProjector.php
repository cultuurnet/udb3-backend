<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership\Readmodels;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;
use CultuurNet\UDB3\Http\Ownership\Search\SearchParameter;
use CultuurNet\UDB3\Http\Ownership\Search\SearchQuery;
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

final class OwnershipSearchProjector implements EventListener
{
    use DelegateEventHandlingToSpecificMethodTrait {
        DelegateEventHandlingToSpecificMethodTrait::handle as handleMethodSpecificEvents;
    }

    private OwnershipSearchRepository $ownershipSearchRepository;
    private DocumentRepository $organizerRepository;
    private SearchByRoleIdAndPermissions $searchByRoleIdAndPermissions;

    public function __construct(OwnershipSearchRepository $ownershipSearchRepository, DocumentRepository $organizerRepository, $searchByRoleIdAndPermissions)
    {
        $this->ownershipSearchRepository = $ownershipSearchRepository;
        $this->organizerRepository = $organizerRepository;
        $this->searchByRoleIdAndPermissions = $searchByRoleIdAndPermissions;
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
        //@todo delete everything with the role because constraitn might have been changed


        $this->processConstraint($constraintEvent->getUuid(), $constraintEvent->getQuery());
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
            if($this->doesUserExistAlready($organizerId, $userId['user_id'])) {
                return;
            }

            $ownershipItem = new OwnershipItem(
                Uuid::uuid4()->toString(),
                $organizerId,
                'organizer',
                $userId['user_id'],
                OwnershipState::approved()->toString()
            );

            $this->ownershipSearchRepository->save(
                $ownershipItem->withRoleId($roleId)
            );
        }
    }

    protected function applyConstraintRemoved(ConstraintRemoved $constraintEvent): void
    {
        $roleId = $constraintEvent->getUuid()->toString();

        die($roleId);
    }

    private function extractUuid(Query $query): ?string
    {
        preg_match('/id:([a-f0-9\-]{36})/', $query->toString(), $matches);
        return $matches[1] ?? null;
    }

    private function doesUserExistAlready(string $organizerId, string $userId) : bool
    {
        $ownerships = $this->ownershipSearchRepository
            ->search(new SearchQuery([
                new SearchParameter('itemId', $organizerId),
                new SearchParameter('ownerId', $userId),
            ], 0, 1));

        return count($ownerships) > 0;
    }
}
