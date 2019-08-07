<?php

namespace CultuurNet\UDB3\UDB2\Event;

use Broadway\Domain\AggregateRoot;
use CultuurNet\UDB3\Cdb\UpdateableWithCdbXmlInterface;
use ValueObjects\StringLiteral\StringLiteral;

/**
 * Implementations create a specific UDB3 entity based on UDB2 cdb xml.
 *
 * The entity created should implement both AggregateRoot and
 * UpdateableWithCdbXmlInterface.
 */
interface EventToUDB3AggregateFactoryInterface
{
    /**
     * @param StringLiteral $id
     * @param StringLiteral $cdbXml
     * @param StringLiteral $cdbXmlNamespaceUri
     * @return UpdateableWithCdbXmlInterface|AggregateRoot
     */
    public function createFromCdbXml(
        StringLiteral $id,
        StringLiteral $cdbXml,
        StringLiteral $cdbXmlNamespaceUri
    );
}
