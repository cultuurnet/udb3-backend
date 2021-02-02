<?php

namespace CultuurNet\UDB3\Model\Validation\ValueObject;

class ConfigurableEnumValidator extends EnumValidator
{
    /**
     * @var string[]
     */
    private $allowedValues;

    /**
     * @param string[] $allowedValues
     */
    public function __construct(array $allowedValues)
    {
        $this->allowedValues = $allowedValues;
        parent::__construct();
    }

    /**
     * @return string[]
     */
    protected function getAllowedValues()
    {
        return $this->allowedValues;
    }
}
