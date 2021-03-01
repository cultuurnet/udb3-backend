<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media;

use Broadway\EventHandling\EventBus;
use Broadway\EventSourcing\AggregateFactory\PublicConstructorAggregateFactory;
use Broadway\EventSourcing\EventSourcingRepository;
use Broadway\EventStore\EventStore;

class MediaObjectRepository extends EventSourcingRepository
{
    public function __construct(
        EventStore $eventStore,
        EventBus $eventBus,
        array $eventStreamDecorators = []
    ) {
        parent::__construct(
            $eventStore,
            $eventBus,
            MediaObject::class,
            new PublicConstructorAggregateFactory(),
            $eventStreamDecorators
        );
    }
}
