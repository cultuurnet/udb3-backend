<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Item;

use Broadway\EventHandling\EventBus;
use Broadway\EventSourcing\AggregateFactory\PublicConstructorAggregateFactory;
use Broadway\EventSourcing\EventSourcingRepository;
use Broadway\EventStore\EventStore;

class ItemRepository extends EventSourcingRepository
{
    public function __construct(
        EventStore $eventStore,
        EventBus $eventBus,
        array $eventStreamDecorators = []
    ) {
        parent::__construct(
            $eventStore,
            $eventBus,
            Item::class,
            new PublicConstructorAggregateFactory(),
            $eventStreamDecorators
        );
    }
}
