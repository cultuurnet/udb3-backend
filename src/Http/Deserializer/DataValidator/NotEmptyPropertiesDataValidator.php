<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\DataValidator;

use CultuurNet\UDB3\Deserializer\DataValidationException;

/**
 * @deprecated
 *   Refactor to implement RequestBodyParser and throw ApiProblemException
 */
class NotEmptyPropertiesDataValidator implements DataValidatorInterface
{
    private array $requiredFields;

    /**
     * @param string[] $requiredFields
     */
    public function __construct(array $requiredFields)
    {
        $this->requiredFields = $requiredFields;
    }

    /**
     * @throws DataValidationException
     */
    public function validate(array $data): void
    {
        $errors = [];

        foreach ($this->requiredFields as $requiredField) {
            if (empty($data[$requiredField])) {
                $errors[$requiredField] = 'Should not be empty.';
            }
        }

        if (!empty($errors)) {
            $exception = new DataValidationException();
            $exception->setValidationMessages($errors);
            throw $exception;
        }
    }
}
