<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\DataValidator;

use CultuurNet\UDB3\Deserializer\DataValidationException;

/**
 * @deprecated
 *   Implement RequestBodyParser instead and throw ApiProblemException
 */
interface DataValidatorInterface
{
    /**
     * @throws DataValidationException
     */
    public function validate(array $data);
}
