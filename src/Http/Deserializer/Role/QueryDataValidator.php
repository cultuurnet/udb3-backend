<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\Role;

use CultuurNet\UDB3\Deserializer\DataValidationException;
use CultuurNet\UDB3\Http\Deserializer\DataValidator\DataValidatorInterface;
use CultuurNet\UDB3\Http\Deserializer\DataValidator\NotEmptyPropertiesDataValidator;

/**
 * @deprecated
 *   Refactor to implement RequestBodyParser and throw ApiProblemException
 */
class QueryDataValidator implements DataValidatorInterface
{
    /**
     * @var NotEmptyPropertiesDataValidator
     */
    private $notEmptyPropertiesDataValidator;

    public function __construct()
    {
        $this->notEmptyPropertiesDataValidator = new NotEmptyPropertiesDataValidator(
            [
                'query',
            ]
        );
    }

    /**
     * @throws DataValidationException
     */
    public function validate(array $data)
    {
        $this->notEmptyPropertiesDataValidator->validate($data);
    }
}
