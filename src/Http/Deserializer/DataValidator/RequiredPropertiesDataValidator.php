<?php

namespace CultuurNet\UDB3\Symfony\Deserializer\DataValidator;

use CultuurNet\Deserializer\DataValidationException;

class RequiredPropertiesDataValidator implements DataValidatorInterface
{
    /**
     * @var string[]
     */
    private $requiredFields;

    /**
     * @param string[] $requiredFields
     */
    public function __construct(array $requiredFields)
    {
        $this->requiredFields = $requiredFields;
    }

    /**
     * @param array $data
     * @throws DataValidationException
     */
    public function validate(array $data)
    {
        $errors = [];

        foreach ($this->requiredFields as $requiredField) {
            if (!isset($data[$requiredField])) {
                $errors[$requiredField] = "Required but could not be found.";
            }
        }

        if (!empty($errors)) {
            $exception = new DataValidationException();
            $exception->setValidationMessages($errors);
            throw $exception;
        }
    }
}
