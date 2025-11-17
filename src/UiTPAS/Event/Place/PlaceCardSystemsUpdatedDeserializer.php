<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPAS\Event\Place;

use CultuurNet\UDB3\Deserializer\JSONDeserializer;
use CultuurNet\UDB3\UiTPAS\Event\CardSystemsUpdatedParser;

/**
 * Deserializes `application/vnd.cultuurnet.uitpas-events.place-card-systems-updated+json` messages
 * to typed objects.
 *
 * Make sure to extract this logic if more (similar) uitpas messages have to be deserialized in the future.
 */
final class PlaceCardSystemsUpdatedDeserializer extends JSONDeserializer
{
    private CardSystemsUpdatedParser $parser;

    public function __construct()
    {
        parent::__construct();
        $this->parser = new CardSystemsUpdatedParser();
    }

    public function deserialize(string $data): PlaceCardSystemsUpdated
    {
        $payload = parent::deserialize($data);

        $id = $this->parser->parseId($payload);
        $cardSystems = $this->parser->parseCardSystems($payload);

        return new PlaceCardSystemsUpdated($id, $cardSystems);
    }
}
