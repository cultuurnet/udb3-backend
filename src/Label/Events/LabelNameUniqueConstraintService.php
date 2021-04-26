<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\Events;

use Broadway\Domain\DomainMessage;
use CultuurNet\UDB3\EventSourcing\DBAL\UniqueConstraintService;

class LabelNameUniqueConstraintService implements UniqueConstraintService
{
    public function hasUniqueConstraint(DomainMessage $domainMessage): bool
    {
        $event = $domainMessage->getPayload();

        return $event instanceof Created;
    }

    public function needsPreflightLookup(): bool
    {
        return false;
    }

    public function needsUpdateUniqueConstraint(DomainMessage $domainMessage): bool
    {
        return false;
    }

    public function getUniqueConstraintValue(DomainMessage $domainMessage): string
    {
        /** @var Created|CopyCreated $event */
        $event = $domainMessage->getPayload();

        return $event->getName()->toNative();
    }
}
