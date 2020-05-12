<?php

namespace CultuurNet\UDB3\Event\ReadModel\JSONLD\Specifications;

use ValueObjects\StringLiteral\StringLiteral;

class HasUiTPASBrand implements EventSpecificationInterface
{
    use Labelable;

    /**
     * @var string[]
     */
    private $UiTPASLabels = ['UiTPAS Regio Aalst', 'UiTPAS Gent', 'Paspartoe'];

    public function isSatisfiedBy($eventLd)
    {
        foreach ($this->UiTPASLabels as $label) {
            if ($this->hasLabel($eventLd, new StringLiteral($label))) {
                return true;
            }
        }

        return false;
    }
}
