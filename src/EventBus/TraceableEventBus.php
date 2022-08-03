<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventBus;

use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventBus;
use Broadway\EventHandling\EventListener;

/**
 * Copy from Broadway\EventHandling\TraceableEventBus, but with an extra getDomainMessages() method for tests that need
 * to e.g. check the metadata of the recorded domain message(s).
 */
final class TraceableEventBus implements EventBus
{
    private EventBus $eventBus;
    private array $recorded = [];
    private bool $tracing = false;

    public function __construct(EventBus $eventBus)
    {
        $this->eventBus = $eventBus;
    }

    public function subscribe(EventListener $eventListener): void
    {
        $this->eventBus->subscribe($eventListener);
    }

    public function publish(DomainEventStream $domainMessages): void
    {
        $this->eventBus->publish($domainMessages);

        if (!$this->tracing) {
            return;
        }

        foreach ($domainMessages as $domainMessage) {
            $this->recorded[] = $domainMessage;
        }
    }

    public function getEvents(): array
    {
        return array_map(
            function (DomainMessage $message) {
                return $message->getPayload();
            },
            $this->recorded
        );
    }

    public function getDomainMessages(): array
    {
        return $this->recorded;
    }

    public function trace(): void
    {
        $this->tracing = true;
    }
}
