<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\JSONLD\Specifications;

use ValueObjects\StringLiteral\StringLiteral;

class Has4Taaliconen implements EventSpecificationInterface
{
    use Labelable;

    public function isSatisfiedBy($eventLd)
    {
        return $this->hasLabel($eventLd, new StringLiteral('vier taaliconen'));
    }
}
