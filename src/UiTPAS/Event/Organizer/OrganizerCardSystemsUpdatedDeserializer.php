<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPAS\Event\Organizer;

use CultuurNet\UDB3\Deserializer\JSONDeserializer;
use CultuurNet\UDB3\UiTPAS\Event\CardSystemsUpdatedParser;

/**
 * Deserializes `application/vnd.cultuurnet.uitpas-events.organizer-card-systems-updated+json` messages
 * to typed objects.
 */
final class OrganizerCardSystemsUpdatedDeserializer extends JSONDeserializer
{
    private CardSystemsUpdatedParser $parser;

    public function __construct()
    {
        parent::__construct();
        $this->parser = new CardSystemsUpdatedParser();
    }

    public function deserialize(string $data): OrganizerCardSystemsUpdated
    {
        $payload = parent::deserialize($data);

        $id = $this->parser->parseId($payload);
        $cardSystems = $this->parser->parseCardSystems($payload);

        return new OrganizerCardSystemsUpdated($id, $cardSystems);
    }
}
