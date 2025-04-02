<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership\Repositories;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;

final class OwnershipItem
{
    private string $id;
    private string $itemId;
    private string $itemType;
    private string $ownerId;
    private string $state;
    private ?Uuid $roleId = null;
    private ?string $approvedBy = null;
    private ?string $rejectedBy = null;
    private ?string $deletedBy = null;

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
    }

    public function withRoleId(Uuid $roleId): self
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

    public function getRoleId(): ?Uuid
    {
        return $this->roleId;
    }

    public function getApprovedBy(): ?string
    {
        return $this->approvedBy;
    }

    public function getRejectedBy(): ?string
    {
        return $this->rejectedBy;
    }

    public function getDeletedBy(): ?string
    {
        return $this->deletedBy;
    }

    public function withApprovedBy(string $userId): self
    {
        $ownershipItem = clone $this;
        $ownershipItem->approvedBy = $userId;
        return $ownershipItem;
    }

    public function withRejectedBy(string $userId): self
    {
        $ownershipItem = clone $this;
        $ownershipItem->rejectedBy = $userId;
        return $ownershipItem;
    }

    public function withDeletedBy(string $userId): self
    {
        $ownershipItem = clone $this;
        $ownershipItem->deletedBy = $userId;
        return $ownershipItem;
    }
}
