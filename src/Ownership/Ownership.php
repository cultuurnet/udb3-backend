<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership;

use Broadway\EventSourcing\EventSourcedAggregateRoot;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;
use CultuurNet\UDB3\Model\ValueObject\Identity\UserId;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Ownership\Events\OwnershipRequested;

final class Ownership extends EventSourcedAggregateRoot
{
    private string $id;

    public function getAggregateRootId(): string
    {
        return $this->id;
    }

    public static function requestOwnership(
        UUID $id,
        UUID $itemId,
        ItemType $itemType,
        UserId $ownerId
    ): self {
        $ownership = new Ownership();

        $ownership->apply(
            new OwnershipRequested(
                $id->toString(),
                $itemId->toString(),
                $itemType->toString(),
                $ownerId->toString()
            )
        );

        return $ownership;
    }

    protected function applyOwnershipRequested(OwnershipRequested $ownershipRequested): void
    {
        $this->id = $ownershipRequested->getId();
    }
}
