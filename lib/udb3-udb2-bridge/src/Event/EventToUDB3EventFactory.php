<?php

namespace CultuurNet\UDB3\UDB2\Event;

use CultuurNet\UDB3\Event\Event;
use ValueObjects\StringLiteral\StringLiteral;

class EventToUDB3EventFactory implements EventToUDB3AggregateFactoryInterface
{
    public function createFromCdbXml(
        StringLiteral $id,
        StringLiteral $cdbXml,
        StringLiteral $cdbXmlNamespaceUri
    ) {
        return Event::importFromUDB2(
            (string)$id,
            (string)$cdbXml,
            (string)$cdbXmlNamespaceUri
        );
    }
}
