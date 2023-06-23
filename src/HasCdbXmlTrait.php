<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

trait HasCdbXmlTrait
{
    protected string $cdbXml;

    protected string $cdbXmlNamespaceUri;

    private function setCdbXml(string $cdbXml)
    {
        if (!is_string($cdbXml)) {
            throw new \InvalidArgumentException(
                'Expected argument 1 to be a scalar string, received ' . gettype($cdbXml)
            );
        }
        $this->cdbXml = $cdbXml;
    }

    private function setCdbXmlNamespaceUri(string $cdbXmlNamespaceUri)
    {
        if (!is_string($cdbXmlNamespaceUri)) {
            throw new \InvalidArgumentException(
                'Expected argument 1 to be a scalar string, received ' . gettype($cdbXmlNamespaceUri)
            );
        }
        $this->cdbXmlNamespaceUri = $cdbXmlNamespaceUri;
    }

    public function getCdbXml(): string
    {
        return $this->cdbXml;
    }

    public function getCdbXmlNamespaceUri(): string
    {
        return $this->cdbXmlNamespaceUri;
    }
}
