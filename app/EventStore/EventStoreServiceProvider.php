<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventStore;

use Broadway\Serializer\SimpleInterfaceSerializer;
use CultuurNet\UDB3\BackwardsCompatiblePayloadSerializerFactory;
use CultuurNet\UDB3\AggregateType;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\EventSourcing\DBAL\AggregateAwareDBALEventStore;
use CultuurNet\UDB3\Labels\LabelServiceProvider;
use League\Container\Argument\Literal\CallableArgument;

final class EventStoreServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            'event_store_factory',
            'eventstore_payload_serializer',
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            'event_store_factory',
            new CallableArgument(
                fn (AggregateType $aggregateType) => new AggregateAwareDBALEventStore(
                    $container->get('dbal_connection'),
                    $container->get('eventstore_payload_serializer'),
                    new SimpleInterfaceSerializer(),
                    'event_store',
                    $aggregateType
                )
            )
        );

        $container->addShared(
            'eventstore_payload_serializer',
            fn () => BackwardsCompatiblePayloadSerializerFactory::createSerializer($container->get(LabelServiceProvider::JSON_READ_REPOSITORY))
        );
    }
}
