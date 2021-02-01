<?php
/**
 * @file
 */

namespace CultuurNet\UDB3;

trait HasCdbXmlTrait
{

    /**
     * @var string
     */
    protected $cdbXml;

    /**
     * @var string
     */
    protected $cdbXmlNamespaceUri;

    /**
     * @param string $cdbXml
     */
    private function setCdbXml($cdbXml)
    {
        if (!is_string($cdbXml)) {
            throw new \InvalidArgumentException(
                'Expected argument 1 to be a scalar string, received ' . gettype($cdbXml)
            );
        }
        $this->cdbXml = $cdbXml;
    }

    /**
     * @param string $cdbXmlNamespaceUri
     */
    private function setCdbXmlNamespaceUri($cdbXmlNamespaceUri)
    {
        if (!is_string($cdbXmlNamespaceUri)) {
            throw new \InvalidArgumentException(
                'Expected argument 1 to be a scalar string, received ' . gettype($cdbXmlNamespaceUri)
            );
        }
        $this->cdbXmlNamespaceUri = $cdbXmlNamespaceUri;
    }

    /**
     * @return string
     */
    public function getCdbXml()
    {
        return $this->cdbXml;
    }

    /**
     * @return string
     */
    public function getCdbXmlNamespaceUri()
    {
        return $this->cdbXmlNamespaceUri;
    }
}
