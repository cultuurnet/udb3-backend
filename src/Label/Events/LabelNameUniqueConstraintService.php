<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\Events;

use Broadway\Domain\DomainMessage;
use CultuurNet\UDB3\EventSourcing\DBAL\UniqueConstraintServiceInterface;
use ValueObjects\StringLiteral\StringLiteral;

class LabelNameUniqueConstraintService implements UniqueConstraintServiceInterface
{
    public function hasUniqueConstraint(DomainMessage $domainMessage): bool
    {
        $event = $domainMessage->getPayload();

        return $event instanceof Created;
    }

    public function needsUpdateUniqueConstraint(DomainMessage $domainMessage): bool
    {
        return false;
    }

    public function getUniqueConstraintValue(DomainMessage $domainMessage): StringLiteral
    {
        /** @var Created|CopyCreated $event */
        $event = $domainMessage->getPayload();

        return $event->getName();
    }
}
