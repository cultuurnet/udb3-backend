<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

interface EventCdbXMLInterface
{
    public function getEventId(): string;

    public function getCdbXml(): string;

    public function getCdbXmlNamespaceUri(): string;
}
