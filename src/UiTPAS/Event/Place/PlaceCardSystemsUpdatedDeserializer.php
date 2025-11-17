<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPAS\Event\Place;

use CultuurNet\UDB3\Deserializer\JSONDeserializer;
use CultuurNet\UDB3\UiTPAS\CardSystem\CardSystem;
use CultuurNet\UDB3\UiTPAS\ValueObject\Id;

/**
 * Deserializes `application/vnd.cultuurnet.uitpas-events.place-card-systems-updated+json` messages
 * to typed objects.
 *
 * Make sure to extract this logic if more (similar) uitpas messages have to be deserialized in the future.
 */
final class PlaceCardSystemsUpdatedDeserializer extends JSONDeserializer
{
    public function deserialize(string $data): PlaceCardSystemsUpdated
    {
        $dto = parent::deserialize($data);

        if (!isset($dto->id)) {
            throw new \InvalidArgumentException('Missing id property.');
        }

        $eventId = new Id((string) $dto->id);

        if (!isset($dto->cardSystems)) {
            throw new \InvalidArgumentException('Missing cardSystems property.');
        }

        if (!is_array($dto->cardSystems)) {
            throw new \InvalidArgumentException('Expected cardSystems property to be an array.');
        }

        $cardSystems = [];
        foreach ($dto->cardSystems as $cardSystemDTO) {
            if (!isset($cardSystemDTO->id)) {
                throw new \InvalidArgumentException('Encountered cardSystems entry without id.');
            }

            if (!isset($cardSystemDTO->name)) {
                throw new \InvalidArgumentException('Encountered cardSystems entry without name.');
            }

            $cardSystems[$cardSystemDTO->id] = new CardSystem(
                new Id((string) $cardSystemDTO->id),
                $cardSystemDTO->name
            );
        }

        return new PlaceCardSystemsUpdated($eventId, $cardSystems);
    }
}
