<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventSourcing;

use Broadway\Domain\DomainEventStream;
use Broadway\EventStore\EventStore;

abstract class AbstractEventStoreDecorator implements EventStore
{
    private EventStore $eventStore;

    public function __construct(EventStore $eventStore)
    {
        $this->eventStore = $eventStore;
    }

    public function load($id): DomainEventStream
    {
        return $this->eventStore->load($id);
    }

    public function loadFromPlayhead($id, int $playhead): DomainEventStream
    {
        return $this->eventStore->loadFromPlayhead($id, $playhead);
    }

    public function append($id, DomainEventStream $eventStream): void
    {
        $this->eventStore->append($id, $eventStream);
    }
}
