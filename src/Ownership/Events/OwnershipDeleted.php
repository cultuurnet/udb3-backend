<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership\Events;

use Broadway\Serializer\Serializable;

final class OwnershipDeleted implements Serializable
{
    private string $id;
    private string $userId;

    public function __construct(string $id, string $userId)
    {
        $this->id = $id;
        $this->userId = $userId;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public static function deserialize(array $data): self
    {
        return new OwnershipDeleted($data['ownershipId'], $data['userId']);
    }

    public function serialize(): array
    {
        return [
            'ownershipId' => $this->id,
            'userId' => $this->userId,
        ];
    }
}
