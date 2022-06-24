<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

use Broadway\Domain\DomainEventStream;
use Broadway\EventHandling\EventBus;
use Broadway\EventHandling\EventListener;
use Broadway\EventHandling\SimpleEventBus as BroadwaySimpleEventBus;

/**
 * Decorator of Broadway's SimpleEventBus with a configurable callback to be
 * executed before the first message is published. This callback can be used to
 * subscribe listeners.
 */
class SimpleEventBus implements EventBus
{
    private $first = true;
    private BroadwaySimpleEventBus $eventBus;

    /**
     * @var null|callable
     */
    private $beforeFirstPublicationCallback;

    public function __construct()
    {
        $this->eventBus = new BroadwaySimpleEventBus();
    }

    public function subscribe(EventListener $eventListener)
    {
        $this->eventBus->subscribe($eventListener);
    }

    /**
     * @param callable $callback
     */
    public function beforeFirstPublication($callback)
    {
        $this->beforeFirstPublicationCallback = $callback;
    }

    private function callBeforeFirstPublicationCallback()
    {
        if ($this->beforeFirstPublicationCallback) {
            $callback = $this->beforeFirstPublicationCallback;
            $callback($this->eventBus);
        }
    }

    public function publish(DomainEventStream $domainMessages): void
    {
        if ($this->first) {
            $this->first = false;
            $this->callBeforeFirstPublicationCallback();
        }

        $this->eventBus->publish($domainMessages);
    }
}
