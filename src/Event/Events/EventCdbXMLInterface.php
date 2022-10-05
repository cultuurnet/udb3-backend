<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

interface EventCdbXMLInterface
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
