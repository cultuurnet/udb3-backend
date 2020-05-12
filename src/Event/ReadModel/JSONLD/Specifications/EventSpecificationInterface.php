<?php

namespace CultuurNet\UDB3\Event\ReadModel\JSONLD\Specifications;

interface EventSpecificationInterface
{
    /**
     * @param \stdClass $eventLd An object representing a json-ld Event
     *
     * @return boolean
     */
    public function isSatisfiedBy($eventLd);
}
