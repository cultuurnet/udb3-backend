<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership\Commands;

use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;
use CultuurNet\UDB3\Model\ValueObject\Identity\UserId;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;

final class RequestOwnership
{
    private Uuid $id;
    private Uuid $itemId;
    private ItemType $itemType;
    private UserId $ownerId;
    private UserId $requesterId;

    public function __construct(
        Uuid $id,
        Uuid $itemId,
        ItemType $itemType,
        UserId $ownerId,
        UserId $requesterId
    ) {
        $this->id = $id;
        $this->itemId = $itemId;
        $this->itemType = $itemType;
        $this->ownerId = $ownerId;
        $this->requesterId = $requesterId;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getItemId(): Uuid
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

    public function getRequesterId(): UserId
    {
        return $this->requesterId;
    }
}
