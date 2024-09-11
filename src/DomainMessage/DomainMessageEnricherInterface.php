<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\DomainMessage;

use Broadway\Domain\DomainMessage;

interface DomainMessageEnricherInterface
{
    public function supports(DomainMessage $domainMessage): bool;

    public function enrich(DomainMessage $domainMessage): DomainMessage;
}
