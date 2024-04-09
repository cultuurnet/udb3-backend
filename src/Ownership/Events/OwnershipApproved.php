<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership\Events;

use Broadway\Serializer\Serializable;

final class OwnershipApproved implements Serializable
{
    private string $id;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public static function deserialize(array $data): self
    {
        return new OwnershipApproved($data['ownershipId']);
    }

    public function serialize(): array
    {
        return [
            'ownershipId' => $this->id,
        ];
    }
}
