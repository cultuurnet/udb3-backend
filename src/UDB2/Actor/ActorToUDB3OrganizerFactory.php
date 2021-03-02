<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UDB2\Actor;

use CultuurNet\UDB3\Organizer\Organizer;

/**
 * Creates UDB3 organizer entities based on UDB2 actor cdb xml.
 */
class ActorToUDB3OrganizerFactory implements ActorToUDB3AggregateFactoryInterface
{
    /**
     * @inheritdoc
     */
    public function createFromCdbXml($id, $cdbXml, $cdbXmlNamespaceUri)
    {
        return Organizer::importFromUDB2(
            $id,
            $cdbXml,
            $cdbXmlNamespaceUri
        );
    }
}
