<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

use CultuurNet\UDB3\Deserializer\JSONDeserializer;
use CultuurNet\UDB3\Deserializer\MissingValueException;
use ValueObjects\StringLiteral\StringLiteral;

/**
 * @deprecated
 *   Refactor to implement RequestBodyParser and throw ApiProblemException
 */
class DescriptionJSONDeserializer extends JSONDeserializer
{
    /**
     * @return Description
     */
    public function deserialize(StringLiteral $data)
    {
        $data = parent::deserialize($data);

        if (!isset($data->description)) {
            throw new MissingValueException('Missing value for "description".');
        }

        return new Description($data->description);
    }
}
