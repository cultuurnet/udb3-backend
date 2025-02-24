<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\AggregateType;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Event\ReadModel\Calendar\CacheCalendarRepository;
use CultuurNet\UDB3\Event\ReadModel\Calendar\EventCalendarProjector;
use CultuurNet\UDB3\EventSourcing\CopyAwareEventStoreDecorator;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Offer\OfferLocator;

final class EventServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            'event_iri_generator',
            'event_store',
            'event_calendar_repository',
            'event_calendar_projector',
            'events_locator_event_stream_decorator',
            'event_repository',
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            'event_iri_generator',
            fn () => new CallableIriGenerator(
                fn ($cdbid) => $container->get('config')['url'] . '/event/' . $cdbid
            )
        );

        $container->addShared(
            'event_store',
            fn () => new CopyAwareEventStoreDecorator(
                $container->get('event_store_factory')(AggregateType::event())
            )
        );

        $container->addShared(
            'event_calendar_repository',
            fn () => new CacheCalendarRepository(
                $container->get('cache')('event_calendar')
            )
        );

        $container->addShared(
            'event_calendar_projector',
            fn () => new EventCalendarProjector(
                $container->get('event_calendar_repository')
            )
        );

        $container->addShared(
            'events_locator_event_stream_decorator',
            fn () => new OfferLocator($container->get('event_iri_generator'))
        );

        $container->addShared(
            'event_repository',
            fn () => new EventRepository(
                $container->get('event_store'),
                $container->get(EventBus::class),
                [
                    $container->get('event_stream_metadata_enricher'),
                    $container->get('events_locator_event_stream_decorator'),
                ]
            )
        );
    }
}
