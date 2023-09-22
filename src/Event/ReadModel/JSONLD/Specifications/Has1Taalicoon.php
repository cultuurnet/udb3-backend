<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\JSONLD\Specifications;

class Has1Taalicoon implements EventSpecificationInterface
{
    use Labelable;

    public function isSatisfiedBy(\stdClass $eventLd): bool
    {
        return $this->hasLabel($eventLd, 'één taalicoon');
    }
}
