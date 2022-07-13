<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventBus;

use Broadway\Domain\DomainEventStream;
use Broadway\EventHandling\EventBus;
use Broadway\EventHandling\EventListener;
use Broadway\EventHandling\SimpleEventBus;

final class MiddlewareEventBus implements EventBus
{
    /** @var EventBusMiddleware[] array */
    private array $middlewares = [];
    private EventBus $eventBus;

    public function __construct(?EventBus $eventBus = null)
    {
        $this->eventBus = $eventBus ?? new SimpleEventBus();
    }

    public function registerMiddleware(EventBusMiddleware $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    public function subscribe(EventListener $eventListener): void
    {
        $this->eventBus->subscribe($eventListener);
    }

    public function publish(DomainEventStream $domainMessages): void
    {
        foreach ($this->middlewares as $middleware) {
            $domainMessages = $middleware->beforePublish($domainMessages);
        }
        $this->eventBus->publish($domainMessages);
    }
}
