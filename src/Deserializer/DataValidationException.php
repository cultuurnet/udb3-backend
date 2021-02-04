<?php

namespace CultuurNet\Deserializer;

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
