<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Model\ValueObject\Web\Urls;
use CultuurNet\UDB3\Offer\Events\AbstractEvent;

final class DeparturePlacesUpdated extends AbstractEvent
{
    public function __construct(string $itemId, public readonly Urls $departurePlaces)
    {
        parent::__construct($itemId);
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'departure_places' => $this->departurePlaces->toStringArray(),
        ];
    }

    public static function deserialize(array $data): self
    {
        $urls = array_map(
            fn (string $url) => new Url($url),
            $data['departure_places']
        );

        return new self($data['item_id'], new Urls(...$urls));
    }
}
