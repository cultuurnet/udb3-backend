<?php

namespace CultuurNet\UDB3\EventSourcing;

use Broadway\Domain\DomainEventStreamInterface;
use Broadway\EventStore\EventStoreInterface;

class AbstractEventStoreDecorator implements EventStoreInterface
{
    /**
     * @var EventStoreInterface
     */
    private $eventStore;

    /**
     * AbstractEventStoreDecorator constructor.
     * @param EventStoreInterface $eventStore
     */
    public function __construct(EventStoreInterface $eventStore)
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
    public function append($id, DomainEventStreamInterface $eventStream)
    {
        $this->eventStore->append($id, $eventStream);
    }
}
