<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Event;

use Broadway\EventHandling\EventBusInterface;
use Broadway\EventSourcing\AggregateFactory\PublicConstructorAggregateFactory;
use Broadway\EventSourcing\EventSourcingRepository;
use Broadway\EventSourcing\EventStreamDecoratorInterface;
use Broadway\EventStore\EventStoreInterface;

/**
 * Class EventRepository
 */
class EventRepository extends EventSourcingRepository
{
    /**
     * @param EventStoreInterface             $eventStore
     * @param EventBusInterface               $eventBus
     * @param EventStreamDecoratorInterface[] $eventStreamDecorators
     */
    public function __construct(
        EventStoreInterface $eventStore,
        EventBusInterface $eventBus,
        array $eventStreamDecorators = array()
    ) {
        parent::__construct(
            $eventStore,
            $eventBus,
            Event::class,
            new PublicConstructorAggregateFactory(),
            $eventStreamDecorators
        );
    }
}
