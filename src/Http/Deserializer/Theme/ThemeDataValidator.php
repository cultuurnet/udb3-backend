<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\Theme;

use CultuurNet\UDB3\Deserializer\DataValidationException;
use CultuurNet\UDB3\Http\Deserializer\DataValidator\DataValidatorInterface;
use CultuurNet\UDB3\Http\Deserializer\DataValidator\RequiredPropertiesDataValidator;

/**
 * @deprecated
 *   Refactor to implement RequestBodyParser and throw ApiProblemException
 */
class ThemeDataValidator implements DataValidatorInterface
{
    private RequiredPropertiesDataValidator $requiredFieldsValidator;

    public function __construct()
    {
        $this->requiredFieldsValidator = new RequiredPropertiesDataValidator(['id', 'label']);
    }

    /**
     * @throws DataValidationException
     */
    public function validate(array $data): void
    {
        $this->requiredFieldsValidator->validate($data);
    }
}
