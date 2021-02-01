<?php

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Cdb\CdbXmlContainerInterface;

interface EventCdbXMLInterface extends CdbXmlContainerInterface
{
    /**
     * @return int
     */
    public function getEventId();

    /**
     * @return string
     */
    public function getCdbXml();

    /**
     * @return string
     */
    public function getCdbXmlNamespaceUri();
}
