<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership\Readmodels;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;
use CultuurNet\UDB3\Ownership\Events\OwnershipRequested;
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

        $ownershipSearchItem = $this->{$handleMethod}($event, $domainMessage);

        $this->ownershipSearchRepository->save($ownershipSearchItem);
    }

    public function applyOwnershipRequested(OwnershipRequested $ownershipRequested): OwnershipItem
    {
        return new OwnershipItem(
            $ownershipRequested->getId(),
            $ownershipRequested->getItemId(),
            $ownershipRequested->getItemType(),
            $ownershipRequested->getOwnerId(),
        );
    }
}
