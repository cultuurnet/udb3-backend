<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\JSONLD\Specifications;

use CultuurNet\UDB3\StringLiteral;

class HasUiTPASBrand implements EventSpecificationInterface
{
    use Labelable;

    /**
     * @var string[]
     */
    private $uitPasLabels = ['UiTPAS Regio Aalst', 'UiTPAS Gent', 'Paspartoe'];

    public function isSatisfiedBy($eventLd)
    {
        foreach ($this->uitPasLabels as $label) {
            if ($this->hasLabel($eventLd, new StringLiteral($label))) {
                return true;
            }
        }

        return false;
    }
}
