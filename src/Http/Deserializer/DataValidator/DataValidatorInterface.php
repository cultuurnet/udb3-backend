<?php

namespace CultuurNet\UDB3\Symfony\Deserializer\DataValidator;

use CultuurNet\Deserializer\DataValidationException;

interface DataValidatorInterface
{
    /**
     * @param array $data
     * @throws DataValidationException
     */
    public function validate(array $data);
}
