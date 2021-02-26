<?php

declare(strict_types=1);
/**
 * @file
 */

namespace CultuurNet\UDB3\Cdb;

interface CdbXmlContainerInterface
{
    /**
     * @return string
     */
    public function getCdbXml();

    /**
     * @return string
     */
    public function getCdbXmlNamespaceUri();
}
