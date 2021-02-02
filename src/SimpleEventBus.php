<?php
/**
 * @file
 */

namespace CultuurNet\UDB3;

use Broadway\Domain\DomainEventStreamInterface;

/**
 * Class SimpleEventBus
 *
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

    public function publish(DomainEventStreamInterface $domainMessages)
    {
        if ($this->first) {
            $this->first = false;
            $this->callBeforeFirstPublicationCallback();
        }

        parent::publish($domainMessages);
    }
}
