<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership\Repositories;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;

final class OwnershipItem
{
    private string $id;
    private string $itemId;
    private string $itemType;
    private string $ownerId;
    private string $state;
    private ?UUID $roleId;

    public function __construct(
        string $id,
        string $itemId,
        string $itemType,
        string $ownerId,
        string $state
    ) {
        $this->id = $id;
        $this->itemId = $itemId;
        $this->itemType = $itemType;
        $this->ownerId = $ownerId;
        $this->state = $state;
        $this->roleId = null;
    }

    public function withRoleId(UUID $roleId): self
    {
        $ownershipItem = clone $this;
        $ownershipItem->roleId = $roleId;
        return $ownershipItem;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getItemId(): string
    {
        return $this->itemId;
    }

    public function getItemType(): string
    {
        return $this->itemType;
    }

    public function getOwnerId(): string
    {
        return $this->ownerId;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getRoleId(): ?UUID
    {
        return $this->roleId;
    }
}
