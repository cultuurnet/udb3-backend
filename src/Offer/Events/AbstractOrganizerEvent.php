<?php

namespace CultuurNet\UDB3\Offer\Events;

abstract class AbstractOrganizerEvent extends AbstractEvent
{
    /**
     * @var string
     */
    protected $organizerId;

    final public function __construct(string $id, $organizerId)
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
        return parent::serialize() + array(
            'organizerId' => $this->organizerId,
        );
    }

    public static function deserialize(array $data): AbstractOrganizerEvent
    {
        return new static(
            $data['item_id'],
            $data['organizerId']
        );
    }
}
