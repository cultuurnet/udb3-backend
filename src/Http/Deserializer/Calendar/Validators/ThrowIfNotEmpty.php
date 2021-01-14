<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\Calendar\Validators;

use CultuurNet\Deserializer\DataValidationException;

trait ThrowIfNotEmpty
{
    private function throwIfNotEmpty(array $messages): void
    {
        if (!empty($messages)) {
            $e = new DataValidationException();
            $e->setValidationMessages($messages);
            throw $e;
        }
    }
}
