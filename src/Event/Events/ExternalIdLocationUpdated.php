<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

interface ExternalIdLocationUpdated
{
    public function getExternalId(): ?string;
}
