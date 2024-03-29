<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPAS\Event\Event;

use CultuurNet\UDB3\Deserializer\JSONDeserializer;
use CultuurNet\UDB3\UiTPAS\CardSystem\CardSystem;
use CultuurNet\UDB3\UiTPAS\ValueObject\Id;

/**
 * Deserializes `application/vnd.cultuurnet.uitpas-events.event-card-systems-updated+json` messages
 * to typed objects.
 *
 * Make sure to extract this logic if more (similar) uitpas messages have to be deserialized in the future.
 */
final class EventCardSystemsUpdatedDeserializer extends JSONDeserializer
{
    public function deserialize(string $data): EventCardSystemsUpdated
    {
        $dto = parent::deserialize($data);

        if (!isset($dto->cdbid)) {
            throw new \InvalidArgumentException('Missing cdbid property.');
        }

        $eventId = new Id((string) $dto->cdbid);

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

        return new EventCardSystemsUpdated($eventId, $cardSystems);
    }
}
