<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Offer\Events\AbstractEvent;

/**
 * Recorded when the ticket sales provider knows the reservation link, with the identifier it uses in its availability
 * updates.
 */
final class ReservationLinkRegistered extends AbstractEvent
{
    public function __construct(
        string $itemId,
        public readonly Url $reservationLink,
        public readonly string $identifier
    ) {
        parent::__construct($itemId);
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'reservation_link' => $this->reservationLink->toString(),
            'identifier' => $this->identifier,
        ];
    }

    public static function deserialize(array $data): self
    {
        return new self($data['item_id'], new Url($data['reservation_link']), $data['identifier']);
    }
}
