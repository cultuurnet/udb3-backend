<?php

namespace CultuurNet\UDB3;

use CultuurNet\Deserializer\JSONDeserializer;
use CultuurNet\Deserializer\MissingValueException;
use ValueObjects\StringLiteral\StringLiteral;

/**
 * @todo Move to udb3-symfony-php.
 * @see https://jira.uitdatabank.be/browse/III-1436
 */
class LabelJSONDeserializer extends JSONDeserializer
{
    /**
     * {@inheritdoc}
     */
    public function deserialize(StringLiteral $data)
    {
        $data = parent::deserialize($data);

        if (empty($data->label)) {
            throw new MissingValueException('Missing value "label"!');
        }

        return new Label($data->label);
    }
}
