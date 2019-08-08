<?php

namespace CultuurNet\UDB3\Http\Deserializer\DataValidator;

use CultuurNet\Deserializer\DataValidationException;

interface DataValidatorInterface
{
    /**
     * @param array $data
     * @throws DataValidationException
     */
    public function validate(array $data);
}
