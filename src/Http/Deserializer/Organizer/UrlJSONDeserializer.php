<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\Organizer;

use CultuurNet\UDB3\Deserializer\JSONDeserializer;
use CultuurNet\UDB3\Deserializer\MissingValueException;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\StringLiteral;

/**
 * @deprecated
 *   Refactor to implement RequestBodyParser and throw ApiProblemException
 */
class UrlJSONDeserializer extends JSONDeserializer
{
    public function deserialize(StringLiteral $data): Url
    {
        $data = parent::deserialize($data);

        if (!isset($data->url)) {
            throw new MissingValueException('Missing value for "url".');
        }

        return new Url($data->url);
    }
}
