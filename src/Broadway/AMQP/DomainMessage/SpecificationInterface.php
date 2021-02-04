<?php

namespace CultuurNet\UDB3\Broadway\AMQP\DomainMessage;

use Broadway\Domain\DomainMessage;

interface SpecificationInterface
{
    public function isSatisfiedBy(DomainMessage $domainMessage);
}
