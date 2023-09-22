<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\JSONLD\Specifications;

class Has3Taaliconen implements EventSpecificationInterface
{
    use Labelable;

    public function isSatisfiedBy(\stdClass $eventLd): bool
    {
        return $this->hasLabel($eventLd, 'drie taaliconen');
    }
}
