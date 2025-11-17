<?php

namespace CultuurNet\UDB3\UiTPAS\Event;

use CultuurNet\UDB3\UiTPAS\CardSystem\CardSystem;
use CultuurNet\UDB3\UiTPAS\ValueObject\Id;

final class CardSystemsUpdatedParser
{
    public function parseId(object $payload): Id
    {
        if (!isset($payload->id) && !isset($payload->cdbid)) {
            throw new \InvalidArgumentException('Missing id or cdbid property.');
        }
        return new Id($payload->id ?? $payload->cdbid);
    }

    /**
     * @return CardSystem[]
     */
    public function parseCardSystems(object $payload): array
    {
        if (!isset($payload->cardSystems)) {
            throw new \InvalidArgumentException('Missing cardSystems property.');
        }

        if (!is_array($payload->cardSystems)) {
            throw new \InvalidArgumentException('Expected cardSystems property to be an array.');
        }

        $cardSystems = [];
        foreach ($payload->cardSystems as $cardSystem) {
            if (!isset($cardSystem->id)) {
                throw new \InvalidArgumentException('Encountered cardSystems entry without id.');
            }

            if (!isset($cardSystem->name)) {
                throw new \InvalidArgumentException('Encountered cardSystems entry without name.');
            }

            $cardSystems[$cardSystem->id] = new CardSystem(
                new Id((string) $cardSystem->id),
                $cardSystem->name
            );
        }

        return $cardSystems;
    }
}
