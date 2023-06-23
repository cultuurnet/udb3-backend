<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\JSONLD\Specifications;

use CultuurNet\UDB3\StringLiteral;

class Has2Taaliconen implements EventSpecificationInterface
{
    use Labelable;

    public function isSatisfiedBy(\stdClass $eventLd): bool
    {
        return $this->hasLabel($eventLd, new StringLiteral('twee taaliconen'));
    }
}
