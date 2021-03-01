<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UDB2\Actor;

use Broadway\Domain\AggregateRoot;
use CultuurNet\UDB3\Cdb\UpdateableWithCdbXmlInterface;

/**
 * Implementations create a specific UDB3 entity based on UDB2 actor cdb xml.
 *
 * The entity created should implement both AggregateRoot and
 * UpdateableWithCdbXmlInterface.
 */
interface ActorToUDB3AggregateFactoryInterface
{
    /**
     * @param string $id
     * @param string $cdbXml
     * @param string $cdbXmlNamespaceUri
     * @return UpdateableWithCdbXmlInterface|AggregateRoot
     */
    public function createFromCdbXml($id, $cdbXml, $cdbXmlNamespaceUri);
}
