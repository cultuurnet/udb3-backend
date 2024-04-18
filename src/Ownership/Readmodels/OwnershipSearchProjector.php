<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership\Readmodels;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;
use CultuurNet\UDB3\Ownership\Events\OwnershipApproved;
use CultuurNet\UDB3\Ownership\Events\OwnershipDeleted;
use CultuurNet\UDB3\Ownership\Events\OwnershipRejected;
use CultuurNet\UDB3\Ownership\Events\OwnershipRequested;
use CultuurNet\UDB3\Ownership\OwnershipState;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItem;
use CultuurNet\UDB3\Ownership\Repositories\Search\OwnershipSearchRepository;

final class OwnershipSearchProjector implements EventListener
{
    use DelegateEventHandlingToSpecificMethodTrait {
        DelegateEventHandlingToSpecificMethodTrait::handle as handleMethodSpecificEvents;
    }

    private OwnershipSearchRepository $ownershipSearchRepository;

    public function __construct(OwnershipSearchRepository $ownershipSearchRepository)
    {
        $this->ownershipSearchRepository = $ownershipSearchRepository;
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
}
