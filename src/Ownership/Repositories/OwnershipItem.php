<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership\Repositories;

final class OwnershipItem
{
    private string $id;
    private string $itemId;
    private string $itemType;
    private string $ownerId;
    private string $state;

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
}
