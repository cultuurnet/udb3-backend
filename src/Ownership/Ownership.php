<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership;

use Broadway\EventSourcing\EventSourcedAggregateRoot;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;
use CultuurNet\UDB3\Model\ValueObject\Identity\UserId;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Ownership\Events\OwnershipApproved;
use CultuurNet\UDB3\Ownership\Events\OwnershipDeleted;
use CultuurNet\UDB3\Ownership\Events\OwnershipRejected;
use CultuurNet\UDB3\Ownership\Events\OwnershipRequested;

final class Ownership extends EventSourcedAggregateRoot
{
    private string $id;
    private OwnershipState $state;

    public function getAggregateRootId(): string
    {
        return $this->id;
    }

    public static function requestOwnership(
        Uuid $id,
        Uuid $itemId,
        ItemType $itemType,
        UserId $ownerId,
        UserId $requesterId
    ): self {
        $ownership = new Ownership();

        $ownership->apply(
            new OwnershipRequested(
                $id->toString(),
                $itemId->toString(),
                $itemType->toString(),
                $ownerId->toString(),
                $requesterId->toString()
            )
        );

        return $ownership;
    }

    public function approve(): void
    {
        if ($this->state->sameAs(OwnershipState::requested())) {
            $this->apply(
                new OwnershipApproved($this->id)
            );
        }
    }

    public function reject(): void
    {
        if ($this->state->sameAs(OwnershipState::requested())) {
            $this->apply(new OwnershipRejected($this->id));
        }
    }

    public function delete(): void
    {
        if (!$this->state->sameAs(OwnershipState::deleted())) {
            $this->apply(new OwnershipDeleted($this->id));
        }
    }

    protected function applyOwnershipRequested(OwnershipRequested $ownershipRequested): void
    {
        $this->id = $ownershipRequested->getId();
        $this->state = OwnershipState::requested();
    }

    protected function applyOwnershipApproved(OwnershipApproved $ownershipApproved): void
    {
        $this->state = OwnershipState::approved();
    }

    protected function applyOwnershipRejected(OwnershipRejected $ownershipRejected): void
    {
        $this->state = OwnershipState::rejected();
    }

    protected function applyOwnershipDeleted(OwnershipDeleted $ownershipDeleted): void
    {
        $this->state = OwnershipState::deleted();
    }
}
