<?php

namespace CultuurNet\UDB3\Event\ReadModel\JSONLD;

use CultuurNet\UDB3\Event\Events\EventProjectedToJSONLD;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
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
     * @param $id
     * @return EventProjectedToJSONLD
     */
    public function createEvent($id)
    {
        return new EventProjectedToJSONLD(
            $id,
            $this->iriGenerator->iri($id)
        );
    }
}
