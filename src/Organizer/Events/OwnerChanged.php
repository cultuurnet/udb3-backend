<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Events;

final class OwnerChanged extends OrganizerEvent
{
    private string $newOwnerId;

    public function __construct(string $organizerId, string $newOwnerId)
    {
        parent::__construct($organizerId);
        $this->newOwnerId = $newOwnerId;
    }

    public function getNewOwnerId(): string
    {
        return $this->newOwnerId;
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'new_owner_id' => $this->newOwnerId,
        ];
    }

    public static function deserialize(array $data): self
    {
        return new OwnerChanged(
            $data['organizer_id'],
            $data['new_owner_id']
        );
    }
}
