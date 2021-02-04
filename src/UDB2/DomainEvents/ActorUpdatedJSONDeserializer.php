<?php

namespace CultuurNet\UDB3\UDB2\DomainEvents;

use CultuurNet\UDB3\Deserializer\JSONDeserializer;
use CultuurNet\UDB3\Deserializer\MissingValueException;
use ValueObjects\StringLiteral\StringLiteral;

class ActorUpdatedJSONDeserializer extends JSONDeserializer
{
    /**
     * @param StringLiteral $json
     * @return ActorUpdated
     */
    public function deserialize(StringLiteral $json)
    {
        $json = parent::deserialize($json);

        if (!isset($json->actorId)) {
            throw new MissingValueException('actorId is missing');
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

        return ActorUpdated::deserialize((array) $json);
    }
}
