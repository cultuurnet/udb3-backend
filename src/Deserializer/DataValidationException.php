<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Deserializer;

class DataValidationException extends \Exception
{
    /**
     * @var string[]
     */
    private $validationMessages = [];

    /**
     * @param string[] $validationMessages
     */
    public function setValidationMessages(array $validationMessages)
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
