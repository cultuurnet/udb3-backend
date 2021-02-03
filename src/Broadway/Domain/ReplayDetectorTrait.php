<?php

namespace CultuurNet\Broadway\Domain;

use Broadway\Domain\DomainMessage;

trait ReplayDetectorTrait
{
    protected function isReplayed(DomainMessage $domainMessage)
    {
        return (new DomainMessageIsReplayed())->isSatisfiedBy($domainMessage);
    }
}
