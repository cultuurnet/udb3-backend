<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Productions;

use CultuurNet\UDB3\Deserializer\DataValidationException;
use CultuurNet\UDB3\Http\Deserializer\DataValidator\DataValidatorInterface;

class RenameProductionValidator implements DataValidatorInterface
{
    public function validate(array $data): void
    {
        if (!isset($data['name'])) {
            $exception = new DataValidationException();
            $exception->setValidationMessages(['Missing required property "name".']);
            throw $exception;
        }

        if (!is_string($data['name']) || empty($data['name'])) {
            $exception = new DataValidationException();
            $exception->setValidationMessages(['Property "name" should be a string with at least one character.']);
            throw $exception;
        }
    }
}
