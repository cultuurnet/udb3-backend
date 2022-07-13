<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventBus;

use Broadway\Domain\DomainEventStream;

interface EventBusMiddleware
{
    /**
     * Gets called by the MiddlewareEventBus before a DomainEventStream is published to the listeners.
     * The middleware may alter the DomainEventStream if needed, and must then return it.
     */
    public function beforePublish(DomainEventStream $domainEventStream): DomainEventStream;
}
