<?php

namespace CultuurNet\BroadwayAMQP\Dummies;

use Broadway\Domain\DomainMessage;
use CultuurNet\BroadwayAMQP\DomainMessage\SpecificationInterface;

class DummyNeverSatisfied implements SpecificationInterface
{
    public function isSatisfiedBy(DomainMessage $domainMessage)
    {
        return false;
    }
}
