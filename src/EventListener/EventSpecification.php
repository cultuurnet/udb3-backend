<?php

namespace CultuurNet\UDB3\EventListener;

interface EventSpecification
{
    public function matches($event);
}
