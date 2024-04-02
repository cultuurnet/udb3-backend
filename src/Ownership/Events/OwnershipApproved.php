<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership\Events;

use Broadway\Serializer\Serializable;

final class OwnershipApproved implements Serializable
{
    private string $id;
    private string $requesterId;

    public function __construct(string $id, string $requesterId)
    {
        $this->id = $id;
        $this->requesterId = $requesterId;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getRequesterId(): string
    {
        return $this->requesterId;
    }

    public static function deserialize(array $data): self
    {
        return new OwnershipApproved(
            $data['ownershipId'],
            $data['requesterId']
        );
    }

    public function serialize(): array
    {
        return [
            'ownershipId' => $this->id,
            'requesterId' => $this->requesterId,
        ];
    }
}
