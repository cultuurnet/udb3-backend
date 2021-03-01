<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label;

use Broadway\EventHandling\EventBus;
use Broadway\EventSourcing\AggregateFactory\PublicConstructorAggregateFactory;
use Broadway\EventSourcing\EventSourcingRepository;
use Broadway\EventStore\EventStore;

class LabelRepository extends EventSourcingRepository
{
    public function __construct(
        EventStore $eventStore,
        EventBus $eventBus,
        array $eventStreamDecorators = []
    ) {
        parent::__construct(
            $eventStore,
            $eventBus,
            Label::class,
            new PublicConstructorAggregateFactory(),
            $eventStreamDecorators
        );
    }
}
