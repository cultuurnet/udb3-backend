<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership\Events;

use Broadway\Serializer\Serializable;

final class OwnershipRequested implements Serializable
{
    private string $id;
    private string $itemId;
    private string $itemType;
    private string $ownerId;

    public function __construct(string $id, string $itemId, string $itemType, string $ownerId)
    {
        $this->id = $id;
        $this->itemId = $itemId;
        $this->itemType = $itemType;
        $this->ownerId = $ownerId;
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

    public static function deserialize(array $data): self
    {
        return new OwnershipRequested(
            $data['ownershipId'],
            $data['itemId'],
            $data['itemType'],
            $data['ownerId']
        );
    }

    public function serialize(): array
    {
        return [
            'ownershipId' => $this->id,
            'itemId' => $this->itemId,
            'itemType' => $this->itemType,
            'ownerId' => $this->ownerId,
        ];
    }
}
