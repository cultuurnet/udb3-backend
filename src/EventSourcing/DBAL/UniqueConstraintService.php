<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventSourcing\DBAL;

use Broadway\Domain\DomainMessage;

interface UniqueConstraintService
{
    public function hasUniqueConstraint(DomainMessage $domainMessage): bool;

    public function needsUpdateUniqueConstraint(DomainMessage $domainMessage): bool;

    public function getUniqueConstraintValue(DomainMessage $domainMessage): string;
}
