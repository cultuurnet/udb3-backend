<?php

namespace CultuurNet\UDB3\Event\ReadModel\JSONLD\Specifications;

use ValueObjects\StringLiteral\StringLiteral;

trait Labelable
{

    /**
     * @param $eventLd
     * @return bool
     */
    public function hasLabel($eventLd, StringLiteral $label)
    {
        if ($label->isEmpty()) {
            throw new \InvalidArgumentException('Label can not be empty');
        }

        return property_exists($eventLd, 'labels') &&
                is_array($eventLd->labels) &&
                in_array((string)$label, $eventLd->labels);
    }
}
