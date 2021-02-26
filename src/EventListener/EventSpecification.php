<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventListener;

interface EventSpecification
{
    public function matches($event);
}
