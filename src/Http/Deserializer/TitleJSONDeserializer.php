<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer;

use CultuurNet\UDB3\Deserializer\JSONDeserializer;
use CultuurNet\UDB3\Deserializer\MissingValueException;
use CultuurNet\UDB3\Title;

/**
 * @deprecated
 *   Refactor to implement RequestBodyParser and throw ApiProblemException
 */
final class TitleJSONDeserializer extends JSONDeserializer
{
    private ?string $propertyName;

    public function __construct(
        bool $assoc = false,
        string $propertyName = null
    ) {
        parent::__construct($assoc);

        if (is_null($propertyName)) {
            $propertyName = 'title';
        }

        $this->propertyName = $propertyName;
    }

    public function deserialize(string $data): Title
    {
        $data = parent::deserialize($data);

        $propertyName = $this->propertyName;

        if (!isset($data->{$propertyName})) {
            throw new MissingValueException("Missing value for \"{$propertyName}\".");
        }

        return new Title($data->{$propertyName});
    }
}
