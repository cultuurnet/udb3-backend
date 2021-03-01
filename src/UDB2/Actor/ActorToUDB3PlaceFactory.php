<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UDB2\Actor;

use CultuurNet\UDB3\Place\Place;

/**
 * Creates UDB3 place entities based on UDB2 event cdb xml.
 */
class ActorToUDB3PlaceFactory implements ActorToUDB3AggregateFactoryInterface
{
    /**
     * @inheritdoc
     */
    public function createFromCdbXml($id, $cdbXml, $cdbXmlNamespaceUri)
    {
        return Place::importFromUDB2Actor(
            (string) $id,
            (string) $cdbXml,
            (string) $cdbXmlNamespaceUri
        );
    }
}
