<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\DomainMessage;

use Broadway\Domain\DomainMessage;

interface DomainMessageEnricherInterface
{
    /**
     * @return bool
     */
    public function supports(DomainMessage $domainMessage);

    /**
     * @return DomainMessage
     */
    public function enrich(DomainMessage $domainMessage);
}
