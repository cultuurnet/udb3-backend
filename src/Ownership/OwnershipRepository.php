<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership;

use Broadway\EventHandling\EventBus;
use Broadway\EventSourcing\AggregateFactory\PublicConstructorAggregateFactory;
use Broadway\EventSourcing\EventSourcingRepository;
use Broadway\EventStore\EventStore;

final class OwnershipRepository extends EventSourcingRepository
{
    public function __construct(
        EventStore $eventStore,
        EventBus $eventBus,
        array $eventStreamDecorators = []
    ) {
        parent::__construct(
            $eventStore,
            $eventBus,
            Ownership::class,
            new PublicConstructorAggregateFactory(),
            $eventStreamDecorators
        );
    }

    public function load($id): Ownership
    {
        $ownership = parent::load($id);

        if (!$ownership instanceof Ownership) {
            throw new \RuntimeException('Expected an Ownership aggregate.');
        }

        return $ownership;
    }
}
