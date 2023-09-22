<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\Theme;

use CultuurNet\UDB3\Deserializer\DataValidationException;
use CultuurNet\UDB3\Deserializer\JSONDeserializer;
use CultuurNet\UDB3\Theme;

/**
 * @deprecated
 *   Refactor to implement RequestBodyParser and throw ApiProblemException
 */
class ThemeJSONDeserializer extends JSONDeserializer
{
    private ThemeDataValidator $validator;

    public function __construct()
    {
        parent::__construct(true);

        $this->validator = new ThemeDataValidator();
    }

    /**
     * @throws DataValidationException
     */
    public function deserialize(string $data): Theme
    {
        $data = parent::deserialize($data);
        $this->validator->validate($data);

        return new Theme($data['id'], $data['label']);
    }
}
