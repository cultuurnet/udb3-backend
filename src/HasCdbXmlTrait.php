<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

trait HasCdbXmlTrait
{
    protected string $cdbXml;

    protected string $cdbXmlNamespaceUri;

    private function setCdbXml(string $cdbXml): void
    {
        $this->cdbXml = $cdbXml;
    }

    private function setCdbXmlNamespaceUri(string $cdbXmlNamespaceUri): void
    {
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
