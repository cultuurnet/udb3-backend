<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Deserializer;

/**
 * @deprecated
 *   Use BodyRequestParser and ApiProblemException instead
 */
class DataValidationException extends \Exception
{
    private array $validationMessages = [];

    /**
     * @param string[] $validationMessages
     */
    public function setValidationMessages(array $validationMessages): void
    {
        $this->validationMessages = $validationMessages;
    }

    /**
     * @return string[]
     */
    public function getValidationMessages()
    {
        return $this->validationMessages;
    }
}
