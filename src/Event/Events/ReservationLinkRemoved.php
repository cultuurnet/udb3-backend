<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Offer\Events\AbstractEvent;

/**
 * Recorded by the event itself when a reservation link is no longer on any of its subEvents.
 */
final class ReservationLinkRemoved extends AbstractEvent
{
    public function __construct(string $itemId, public readonly Url $reservationLink)
    {
        parent::__construct($itemId);
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'reservation_link' => $this->reservationLink->toString(),
        ];
    }

    public static function deserialize(array $data): self
    {
        return new self($data['item_id'], new Url($data['reservation_link']));
    }
}
