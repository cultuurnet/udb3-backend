<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer;

use CultuurNet\UDB3\Deserializer\JSONDeserializer;
use CultuurNet\UDB3\Deserializer\MissingValueException;
use CultuurNet\UDB3\Title;
use ValueObjects\StringLiteral\StringLiteral;

/**
 * @deprecated
 *   Refactor to implement RequestBodyParser and throw ApiProblemException
 */
class TitleJSONDeserializer extends JSONDeserializer
{
    /**
     * @var StringLiteral
     */
    private $propertyName;

    /**
     * TitleJSONDeserializer constructor.
     * @param bool $assoc
     */
    public function __construct(
        $assoc = false,
        StringLiteral $propertyName = null
    ) {
        parent::__construct($assoc);

        if (is_null($propertyName)) {
            $propertyName = new StringLiteral('title');
        }

        $this->propertyName = $propertyName;
    }

    /**
     * @return Title
     */
    public function deserialize(StringLiteral $data)
    {
        $data = parent::deserialize($data);

        $propertyName = $this->propertyName->toNative();

        if (!isset($data->{$propertyName})) {
            throw new MissingValueException("Missing value for \"{$propertyName}\".");
        }

        return new Title($data->{$propertyName});
    }
}
