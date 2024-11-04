<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Broadway\Domain;

use Broadway\Domain\DomainMessage;

trait ReplayDetectorTrait
{
    protected function isReplayed(DomainMessage $domainMessage): bool
    {
        return (new DomainMessageIsReplayed())->isSatisfiedBy($domainMessage);
    }
}
