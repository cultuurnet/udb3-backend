<?php

namespace CultuurNet\UDB3\Place;

use Assert\Assertion as Assert;
use Broadway\Domain\AggregateRoot;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainEventStreamInterface;
use Broadway\EventHandling\EventBusInterface;
use Broadway\EventSourcing\AggregateFactory\PublicConstructorAggregateFactory;
use Broadway\EventSourcing\EventSourcingRepository;
use Broadway\EventSourcing\EventStreamDecoratorInterface;
use Broadway\EventStore\EventStoreInterface;

class PlaceRepository extends EventSourcingRepository
{
    private const AGGREGATE_CLASS = Place::class;

    /**
     * @var EventStoreInterface
     */
    protected $protectedEventStore;

    /**
     * @var EventBusInterface
     */
    protected $protectedEventBus;

    /**
     * @var array|EventStreamDecoratorInterface[]
     */
    protected $protectedEventStreamDecorators;

    /**
     * @param EventStoreInterface $eventStore
     * @param EventBusInterface $eventBus
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
            self::AGGREGATE_CLASS,
            new PublicConstructorAggregateFactory(),
            $eventStreamDecorators
        );

        $this->protectedEventStore = $eventStore;
        $this->protectedEventBus = $eventBus;
        $this->protectedEventStreamDecorators = $eventStreamDecorators;
    }

    public function saveMultiple(AggregateRoot ...$aggregates): void
    {
        if (empty($aggregates)) {
            return;
        }

        $firstId = null;
        $domainEvents = [];
        foreach ($aggregates as $aggregate) {
            Assert::isInstanceOf($aggregate, self::AGGREGATE_CLASS);

            if (!$firstId) {
                // We need to pass an aggregate id to the EventStore::append() method, but it's not actually used for
                // anything except for a check that it's a string. So we just pass the one of the first aggregate.
                $firstId = $aggregate->getAggregateRootId();
            }

            $eventStream = $this->protectedDecorateForWrite($aggregate, $aggregate->getUncommittedEvents());

            $domainEvents = array_merge(
                $domainEvents,
                iterator_to_array($eventStream->getIterator())
            );
        }

        $eventStream = new DomainEventStream($domainEvents);

        $this->protectedEventStore->append($firstId, $eventStream);
        $this->protectedEventBus->publish($eventStream);
    }

    protected function protectedDecorateForWrite(
        AggregateRoot $aggregate,
        DomainEventStreamInterface $eventStream
    ): DomainEventStreamInterface {
        $aggregateIdentifier = $aggregate->getAggregateRootId();

        foreach ($this->protectedEventStreamDecorators as $eventStreamDecorator) {
            $eventStream = $eventStreamDecorator->decorateForWrite(
                self::AGGREGATE_CLASS,
                $aggregateIdentifier,
                $eventStream
            );
        }

        return $eventStream;
    }
}
