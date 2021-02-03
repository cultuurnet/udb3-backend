<?php

namespace CultuurNet\Broadway\Domain;

use Broadway\Domain\DomainMessage;

interface DomainMessageSpecificationInterface
{
    /**
     * @param DomainMessage $domainMessage
     * @return bool
     */
    public function isSatisfiedBy(DomainMessage $domainMessage);
}
