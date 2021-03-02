<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer;

use Broadway\EventHandling\EventBus;
use Broadway\EventSourcing\AggregateFactory\PublicConstructorAggregateFactory;
use Broadway\EventSourcing\EventSourcingRepository;
use Broadway\EventStore\EventStore;

class OrganizerRepository extends EventSourcingRepository
{
    public function __construct(
        EventStore $eventStore,
        EventBus $eventBus,
        array $eventStreamDecorators = []
    ) {
        parent::__construct(
            $eventStore,
            $eventBus,
            Organizer::class,
            new PublicConstructorAggregateFactory(),
            $eventStreamDecorators
        );
    }

    public function load($id): Organizer
    {
        // Overwritten to add better return type based on the Aggregate class passed in the constructor.
        // Docblock added for PHPStan
        /** @var Organizer $organizer */
        $organizer = parent::load($id);
        return $organizer;
    }
}
