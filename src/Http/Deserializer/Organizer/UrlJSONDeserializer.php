<?php

namespace CultuurNet\UDB3\Http\Deserializer\Organizer;

use CultuurNet\UDB3\Deserializer\JSONDeserializer;
use CultuurNet\UDB3\Deserializer\MissingValueException;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\Url;

class UrlJSONDeserializer extends JSONDeserializer
{
    /**
     * @return Url
     */
    public function deserialize(StringLiteral $data)
    {
        $data = parent::deserialize($data);

        if (!isset($data->url)) {
            throw new MissingValueException('Missing value for "url".');
        }

        return Url::fromNative($data->url);
    }
}
