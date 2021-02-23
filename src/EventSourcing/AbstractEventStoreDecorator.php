<?php

namespace CultuurNet\UDB3\EventSourcing;

use Broadway\Domain\DomainEventStream;
use Broadway\EventStore\EventStore;

abstract class AbstractEventStoreDecorator implements EventStore
{
    /**
     * @var EventStore
     */
    private $eventStore;

    public function __construct(EventStore $eventStore)
    {
        $this->eventStore = $eventStore;
    }

    /**
     * @inheritdoc
     */
    public function load($id)
    {
        return $this->eventStore->load($id);
    }

    /**
     * @inheritdoc
     */
    public function loadFromPlayhead($id, $playhead)
    {
        return $this->eventStore->loadFromPlayhead($id, $playhead);
    }

    /**
     * @inheritdoc
     */
    public function append($id, DomainEventStream $eventStream)
    {
        $this->eventStore->append($id, $eventStream);
    }
}
