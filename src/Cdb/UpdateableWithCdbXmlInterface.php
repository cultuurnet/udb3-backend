<?php

namespace CultuurNet\UDB3\Cdb;

interface UpdateableWithCdbXmlInterface
{
    /**
     * @param string $cdbXml
     * @param string $cdbXmlNamespaceUri
     * @return void
     */
    public function updateWithCdbXml($cdbXml, $cdbXmlNamespaceUri);
}
