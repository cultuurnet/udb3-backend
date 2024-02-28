<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership\Commands;

use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;
use CultuurNet\UDB3\Model\ValueObject\Identity\UserId;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;

final class RequestOwnership
{
    private UUID $id;
    private UUID $itemId;
    private ItemType $itemType;
    private UserId $ownerId;

    public function __construct(UUID $id, UUID $itemId, ItemType $itemType, UserId $ownerId)
    {
        $this->id = $id;
        $this->itemId = $itemId;
        $this->itemType = $itemType;
        $this->ownerId = $ownerId;
    }

    public function getId(): UUID
    {
        return $this->id;
    }

    public function getItemId(): UUID
    {
        return $this->itemId;
    }

    public function getItemType(): ItemType
    {
        return $this->itemType;
    }

    public function getOwnerId(): UserId
    {
        return $this->ownerId;
    }
}
