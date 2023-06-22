<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\JSONLD\Specifications;

interface EventSpecificationInterface
{
    public function isSatisfiedBy(\stdClass $eventLd): bool;
}
