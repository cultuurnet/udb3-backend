<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\Address;

use CultuurNet\UDB3\Deserializer\DataValidationException;
use CultuurNet\UDB3\Http\Deserializer\DataValidator\DataValidatorInterface;
use CultuurNet\UDB3\Http\Deserializer\DataValidator\NotEmptyPropertiesDataValidator;

/**
 * @deprecated
 *   Refactor to implement RequestBodyParser and throw ApiProblemException
 */
class AddressDataValidator implements DataValidatorInterface
{
    private NotEmptyPropertiesDataValidator $notEmptyPropertiesDataValidator;

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
     * @throws DataValidationException
     */
    public function validate(array $data): void
    {
        $this->notEmptyPropertiesDataValidator->validate($data);
    }
}
