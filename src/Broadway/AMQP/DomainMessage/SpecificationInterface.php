<?php

namespace CultuurNet\BroadwayAMQP\DomainMessage;

use Broadway\Domain\DomainMessage;

interface SpecificationInterface
{
    public function isSatisfiedBy(DomainMessage $domainMessage);
}
