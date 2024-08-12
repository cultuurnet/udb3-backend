<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Events;

use CultuurNet\UDB3\DateTimeFactory;
use DateTimeInterface;

abstract class AbstractAvailableFromUpdated extends AbstractEvent
{
    private DateTimeInterface $availableFrom;

    final public function __construct(string $itemId, DateTimeInterface $availableFrom)
    {
        parent::__construct($itemId);

        $this->availableFrom = $availableFrom;
    }

    public function getAvailableFrom(): DateTimeInterface
    {
        return $this->availableFrom;
    }

    public function serialize(): array
    {
        return parent::serialize() + [
                'available_from' => $this->availableFrom->format(DateTimeInterface::ATOM),
            ];
    }

    public static function deserialize(array $data): AbstractAvailableFromUpdated
    {
        return new static(
            $data['item_id'],
            DateTimeFactory::fromAtom($data['available_from'])
        );
    }
}
