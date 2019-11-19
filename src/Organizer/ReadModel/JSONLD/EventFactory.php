<?php

namespace CultuurNet\UDB3\Organizer\ReadModel\JSONLD;

use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Organizer\OrganizerProjectedToJSONLD;
use CultuurNet\UDB3\ReadModel\DocumentEventFactory;

class EventFactory implements DocumentEventFactory
{
    /**
     * @var IriGeneratorInterface
     */
    private $iriGenerator;

    /**
     * @param IriGeneratorInterface $iriGenerator
     */
    public function __construct(IriGeneratorInterface $iriGenerator)
    {
        $this->iriGenerator = $iriGenerator;
    }

    /**
     * @param string $id
     * @return OrganizerProjectedToJSONLD
     */
    public function createEvent($id)
    {
        return new OrganizerProjectedToJSONLD(
            $id,
            $this->iriGenerator->iri($id)
        );
    }
}
