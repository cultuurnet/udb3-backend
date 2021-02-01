<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Events;

use Broadway\Serializer\SerializableInterface;

abstract class AbstractOwnerChanged implements SerializableInterface
{
    /**
     * @var string
     */
    private $offerId;

    /**
     * @var string
     */
    private $newOwnerId;

    final public function __construct(string $offerId, string $newOwnerId)
    {
        $this->offerId = $offerId;
        $this->newOwnerId = $newOwnerId;
    }

    public function getOfferId(): string
    {
        return $this->offerId;
    }

    public function getNewOwnerId(): string
    {
        return $this->newOwnerId;
    }

    public function serialize(): array
    {
        return array(
            'offer_id' => $this->offerId,
            'new_owner_id' => $this->newOwnerId,
        );
    }

    public static function deserialize(array $data): self
    {
        return new static($data['offer_id'], $data['new_owner_id']);
    }
}
