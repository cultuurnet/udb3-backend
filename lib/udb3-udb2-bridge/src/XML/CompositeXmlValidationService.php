<?php

namespace CultuurNet\UDB3\UDB2\XML;

class CompositeXmlValidationService implements XMLValidationServiceInterface
{
    /**
     * @var XMLValidationServiceInterface[]
     */
    private $xmlValidationServices;

    /**
     * @param XMLValidationServiceInterface[] $xmlValidationServices
     */
    public function __construct(XMLValidationServiceInterface... $xmlValidationServices)
    {
        $this->xmlValidationServices = $xmlValidationServices;
    }

    /**
     * @inheritdoc
     */
    public function validate($xml)
    {
        $errors = [];
        foreach ($this->xmlValidationServices as $xmlValidationService) {
            $errors = array_merge($errors, $xmlValidationService->validate($xml));
        }
        return $errors;
    }
}
