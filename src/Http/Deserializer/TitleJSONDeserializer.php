<?php

namespace CultuurNet\UDB3\Symfony\Deserializer;

use CultuurNet\Deserializer\JSONDeserializer;
use CultuurNet\Deserializer\MissingValueException;
use CultuurNet\UDB3\Title;
use ValueObjects\StringLiteral\StringLiteral;

class TitleJSONDeserializer extends JSONDeserializer
{
    /**
     * @var StringLiteral
     */
    private $propertyName;

    /**
     * TitleJSONDeserializer constructor.
     * @param bool $assoc
     * @param StringLiteral|null $propertyName
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
     * @param StringLiteral $data
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
