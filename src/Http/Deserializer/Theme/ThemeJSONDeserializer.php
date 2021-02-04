<?php

namespace CultuurNet\UDB3\Http\Deserializer\Theme;

use CultuurNet\UDB3\Deserializer\DataValidationException;
use CultuurNet\UDB3\Deserializer\JSONDeserializer;
use CultuurNet\UDB3\Theme;
use ValueObjects\StringLiteral\StringLiteral;

class ThemeJSONDeserializer extends JSONDeserializer
{
    /**
     * @var ThemeDataValidator
     */
    private $validator;

    public function __construct()
    {
        $assoc = true;
        parent::__construct($assoc);

        $this->validator = new ThemeDataValidator();
    }

    /**
     * @param StringLiteral $data
     * @return Theme
     * @throws DataValidationException
     */
    public function deserialize(StringLiteral $data)
    {
        $data = parent::deserialize($data);
        $this->validator->validate($data);

        return new Theme($data['id'], $data['label']);
    }
}
