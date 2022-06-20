<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

use CultuurNet\UDB3\Deserializer\JSONDeserializer;
use CultuurNet\UDB3\Deserializer\MissingValueException;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;

/**
 * @deprecated
 *   Refactor to implement RequestBodyParser and throw ApiProblemException
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

        return new Label(new LabelName( $data->label));
    }
}
