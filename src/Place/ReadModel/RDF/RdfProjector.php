<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\RDF;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;

final class RdfProjector implements EventListener
{
    public function handle(DomainMessage $domainMessage): void
    {
    }
}
