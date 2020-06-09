<?php

namespace CultuurNet\UDB3\Http\Productions;

use CultuurNet\Deserializer\DataValidationException;
use CultuurNet\UDB3\Http\Deserializer\DataValidator\DataValidatorInterface;

class CreateProductionValidator implements DataValidatorInterface
{
    public function validate(array $data)
    {
        throw new DataValidationException();
    }
}
