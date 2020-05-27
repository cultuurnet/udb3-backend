<?php

namespace CultuurNet\UDB3\Event\ReadModel\JSONLD\Specifications;

use ValueObjects\StringLiteral\StringLiteral;

class Has1Taalicoon implements EventSpecificationInterface
{
    use Labelable;

    public function isSatisfiedBy($eventLd)
    {
        return $this->hasLabel($eventLd, new StringLiteral('één taalicoon'));
    }
}
