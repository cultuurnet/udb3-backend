<?php

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\Deserializer\JSONDeserializer;
use CultuurNet\Deserializer\MissingValueException;
use CultuurNet\UDB3\Label;
use ValueObjects\StringLiteral\StringLiteral;

/**
 * @todo Move to udb3-symfony-php.
 * @see https://jira.uitdatabank.be/browse/III-1436
 */
class AddLabelToQueryJSONDeserializer extends JSONDeserializer
{
    public function deserialize(StringLiteral $data)
    {
        $data = parent::deserialize($data);

        if (empty($data->label)) {
            throw new MissingValueException('Missing value "label".');
        }
        if (empty($data->query)) {
            throw new MissingValueException('Missing value "query".');
        }

        return new AddLabelToQuery(
            $data->query,
            new Label($data->label)
        );
    }
}
