<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Deserializer\JSONDeserializer;
use CultuurNet\UDB3\Deserializer\MissingValueException;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\StringLiteral;

/**
 * @deprecated
 *   Refactor to implement RequestBodyParser and throw ApiProblemException
 */
class AddLabelToQueryJSONDeserializer extends JSONDeserializer
{
    public function deserialize(StringLiteral $data): AddLabelToQuery
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
            new Label(new LabelName($data->label))
        );
    }
}
