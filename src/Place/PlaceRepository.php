<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use Assert\Assertion as Assert;
use Broadway\Domain\AggregateRoot;
use Broadway\Domain\DomainEventStream;
use Broadway\EventHandling\EventBus;
use Broadway\EventSourcing\AggregateFactory\PublicConstructorAggregateFactory;
use Broadway\EventSourcing\EventSourcingRepository;
use Broadway\EventSourcing\EventStreamDecorator;
use Broadway\EventStore\EventStore;

class PlaceRepository extends EventSourcingRepository
{
    private const AGGREGATE_CLASS = Place::class;

    /**
     * @var EventStore
     */
    protected $protectedEventStore;

    /**
     * @var EventBus
     */
    protected $protectedEventBus;

    /**
     * @var array|EventStreamDecorator[]
     */
    protected $protectedEventStreamDecorators;

    public function __construct(
        EventStore $eventStore,
        EventBus $eventBus,
        array $eventStreamDecorators = []
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
        DomainEventStream $eventStream
    ): DomainEventStream {
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
