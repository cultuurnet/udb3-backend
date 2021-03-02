<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

use Broadway\Domain\DomainEventStream;

/**
 * Extension of Broadway's SimpleEventBus with a configurable callback to be
 * executed before the first message is published. This callback can be used to
 * subscribe listeners.
 */
class SimpleEventBus extends \Broadway\EventHandling\SimpleEventBus
{
    private $first = true;

    /**
     * @var null|callable
     */
    private $beforeFirstPublicationCallback;

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
            $callback($this);
        }
    }

    public function publish(DomainEventStream $domainMessages): void
    {
        if ($this->first) {
            $this->first = false;
            $this->callBeforeFirstPublicationCallback();
        }

        parent::publish($domainMessages);
    }
}
