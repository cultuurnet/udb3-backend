<?php

namespace CultuurNet\BroadwayAMQP\Dummies;

use Broadway\Domain\DomainMessage;
use CultuurNet\BroadwayAMQP\DomainMessage\SpecificationInterface;

class DummyAlwaysSatisfied implements SpecificationInterface
{
    public function isSatisfiedBy(DomainMessage $domainMessage)
    {
        return true;
    }
}
