<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Broadway\Domain;

use Broadway\Domain\DomainMessage;

interface DomainMessageSpecificationInterface
{
    public function isSatisfiedBy(DomainMessage $domainMessage): bool;
}
