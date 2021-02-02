<?php

namespace CultuurNet\UDB3\Label\Events;

use Broadway\Domain\DomainMessage;
use CultuurNet\UDB3\EventSourcing\DBAL\UniqueConstraintServiceInterface;

class LabelNameUniqueConstraintService implements UniqueConstraintServiceInterface
{
    /**
     * @inheritdoc
     */
    public function hasUniqueConstraint(DomainMessage $domainMessage)
    {
        $event = $domainMessage->getPayload();

        return ($event instanceof Created ||
            $event instanceof CopyCreated);
    }

    /**
     * @inheritdoc
     */
    public function needsUpdateUniqueConstraint(DomainMessage $domainMessage)
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function getUniqueConstraintValue(DomainMessage $domainMessage)
    {
        /** @var Created|CopyCreated $event */
        $event = $domainMessage->getPayload();

        return $event->getName();
    }
}
