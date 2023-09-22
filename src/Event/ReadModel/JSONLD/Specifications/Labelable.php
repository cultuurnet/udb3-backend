<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\JSONLD\Specifications;

trait Labelable
{
    public function hasLabel(\stdClass $eventLd, string $label): bool
    {
        if ($label === '') {
            throw new \InvalidArgumentException('Label can not be empty');
        }

        return property_exists($eventLd, 'labels') &&
                is_array($eventLd->labels) &&
            in_array($label, $eventLd->labels, true);
    }
}
