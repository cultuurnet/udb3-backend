<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UDB2\DomainEvents;

use CultuurNet\UDB3\Deserializer\JSONDeserializer;
use CultuurNet\UDB3\Deserializer\MissingValueException;
use ValueObjects\StringLiteral\StringLiteral;

class EventUpdatedJSONDeserializer extends JSONDeserializer
{
    public function deserialize(StringLiteral $json)
    {
        $json = parent::deserialize($json);

        if (!isset($json->eventId)) {
            throw new MissingValueException('eventId is missing');
        }

        if (!isset($json->time)) {
            throw new MissingValueException('time is missing');
        }

        if (!isset($json->author)) {
            throw new MissingValueException('author is missing');
        }

        if (!isset($json->url)) {
            throw new MissingValueException('url is missing');
        }

        return EventUpdated::deserialize((array) $json);
    }
}
