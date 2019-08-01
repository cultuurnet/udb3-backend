<?php

namespace CultuurNet\UDB3\Symfony\Deserializer\Role;

use CultuurNet\Deserializer\DataValidationException;
use CultuurNet\UDB3\Symfony\Deserializer\DataValidator\DataValidatorInterface;
use CultuurNet\UDB3\Symfony\Deserializer\DataValidator\NotEmptyPropertiesDataValidator;
use CultuurNet\UDB3\Symfony\Deserializer\DataValidator\RequiredPropertiesDataValidator;

class QueryDataValidator implements DataValidatorInterface
{
    /**
     * @var RequiredPropertiesDataValidator
     */
    private $requiredFieldsValidator;

    public function __construct()
    {
        $this->requiredFieldsValidator = new NotEmptyPropertiesDataValidator(
            [
                'query'
            ]
        );
    }

    /**
     * @param array $data
     * @throws DataValidationException
     */
    public function validate(array $data)
    {
        $this->requiredFieldsValidator->validate($data);
    }
}
