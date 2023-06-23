<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer;

use CultuurNet\UDB3\Deserializer\JSONDeserializer;
use CultuurNet\UDB3\Deserializer\MissingValueException;
use CultuurNet\UDB3\Title;
use CultuurNet\UDB3\StringLiteral;

/**
 * @deprecated
 *   Refactor to implement RequestBodyParser and throw ApiProblemException
 */
class TitleJSONDeserializer extends JSONDeserializer
{
    private ?StringLiteral $propertyName;

    public function __construct(
        bool $assoc = false,
        StringLiteral $propertyName = null
    ) {
        parent::__construct($assoc);

        if (is_null($propertyName)) {
            $propertyName = new StringLiteral('title');
        }

        $this->propertyName = $propertyName;
    }

    public function deserialize(StringLiteral $data): Title
    {
        $data = parent::deserialize($data);

        $propertyName = $this->propertyName->toNative();

        if (!isset($data->{$propertyName})) {
            throw new MissingValueException("Missing value for \"{$propertyName}\".");
        }

        return new Title($data->{$propertyName});
    }
}
