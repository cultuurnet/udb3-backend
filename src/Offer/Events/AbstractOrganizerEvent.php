<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Events;

abstract class AbstractOrganizerEvent extends AbstractEvent
{
    protected string $organizerId;

    final public function __construct(string $id, string $organizerId)
    {
        parent::__construct($id);
        $this->organizerId = $organizerId;
    }

    public function getOrganizerId(): string
    {
        return $this->organizerId;
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'organizerId' => $this->organizerId,
        ];
    }

    public static function deserialize(array $data): AbstractOrganizerEvent
    {
        return new static(
            $data['item_id'],
            $data['organizerId']
        );
    }
}
