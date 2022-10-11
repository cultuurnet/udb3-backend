<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use CultuurNet\UDB3\AggregateType;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\EventSourcing\DBAL\AggregateAwareDBALEventStore;

final class EventStoreServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            'event_store_factory',
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            'event_store_factory',
            fn (AggregateType $aggregateType) => new AggregateAwareDBALEventStore(
                $container->get('dbal_connection'),
                $container->get('eventstore_payload_serializer'),
                new \Broadway\Serializer\SimpleInterfaceSerializer(),
                'event_store',
                $aggregateType
            )
        );
    }
}
