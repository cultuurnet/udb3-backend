<?php

namespace CultuurNet\UDB3\Http\Deserializer\Address;

use CultuurNet\UDB3\Deserializer\DataValidationException;
use CultuurNet\UDB3\Http\Deserializer\DataValidator\DataValidatorInterface;
use CultuurNet\UDB3\Http\Deserializer\DataValidator\NotEmptyPropertiesDataValidator;

class AddressDataValidator implements DataValidatorInterface
{
    /**
     * @var NotEmptyPropertiesDataValidator
     */
    private $notEmptyPropertiesDataValidator;

    public function __construct()
    {
        $this->notEmptyPropertiesDataValidator = new NotEmptyPropertiesDataValidator(
            [
                'streetAddress',
                'postalCode',
                'addressLocality',
                'addressCountry',
            ]
        );
    }

    /**
     * @param array $data
     * @throws DataValidationException
     */
    public function validate(array $data)
    {
        $this->notEmptyPropertiesDataValidator->validate($data);
    }
}
