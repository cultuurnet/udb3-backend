<?php

namespace CultuurNet\UDB3\Symfony\Deserializer\Event;

use CultuurNet\Deserializer\DataValidationException;
use CultuurNet\UDB3\Symfony\Deserializer\DataValidator\DataValidatorInterface;
use CultuurNet\UDB3\Symfony\Deserializer\DataValidator\RequiredPropertiesDataValidator;

class EventTypeDataValidator implements DataValidatorInterface
{
    /**
     * @var RequiredPropertiesDataValidator
     */
    private $requiredFieldsValidator;

    public function __construct()
    {
        $this->requiredFieldsValidator = new RequiredPropertiesDataValidator(['id', 'label']);
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
