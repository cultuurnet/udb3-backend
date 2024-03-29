<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\ReadModel\JSONLD;

use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Organizer\OrganizerProjectedToJSONLD;
use CultuurNet\UDB3\ReadModel\DocumentEventFactory;

class EventFactory implements DocumentEventFactory
{
    private IriGeneratorInterface $iriGenerator;

    public function __construct(IriGeneratorInterface $iriGenerator)
    {
        $this->iriGenerator = $iriGenerator;
    }

    public function createEvent(string $id): OrganizerProjectedToJSONLD
    {
        return new OrganizerProjectedToJSONLD(
            $id,
            $this->iriGenerator->iri($id)
        );
    }
}
