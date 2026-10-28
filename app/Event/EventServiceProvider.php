<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\AggregateType;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Error\LoggerFactory;
use CultuurNet\UDB3\Error\LoggerName;
use CultuurNet\UDB3\Event\ReadModel\Calendar\CacheCalendarRepository;
use CultuurNet\UDB3\Event\ReadModel\Calendar\EventCalendarProjector;
use CultuurNet\UDB3\EventSourcing\CopyAwareEventStoreDecorator;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Offer\OfferLocator;
use CultuurNet\UDB3\Ticketing\Providers\Ticketmatic\HttpTicketmaticProvider;
use CultuurNet\UDB3\Ticketing\Providers\TicketingProvider;
use CultuurNet\UDB3\Ticketing\Repositories\DBALTicketingRegistrationRepository;
use CultuurNet\UDB3\Ticketing\Repositories\TicketingRegistrationRepository;
use CultuurNet\UDB3\Ticketing\TicketingRegistrationsProjector;
use GuzzleHttp\Client;

final class EventServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            'event_iri_generator',
            'event_store',
            'event_calendar_repository',
            'event_calendar_projector',
            TicketingRegistrationRepository::class,
            TicketingRegistrationsProjector::class,
            TicketingProvider::class,
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
                fn ($cdbid) => $container->get('config')['url'] . '/events/' . $cdbid
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
            TicketingRegistrationRepository::class,
            fn () => new DBALTicketingRegistrationRepository($container->get('dbal_connection'))
        );

        $container->addShared(
            TicketingRegistrationsProjector::class,
            fn () => new TicketingRegistrationsProjector(
                $container->get(TicketingRegistrationRepository::class),
                $container->get('config')['ticketing']['ticketmatic']['reservation_link_hosts'] ?? []
            )
        );

        $container->addShared(
            TicketingProvider::class,
            function () use ($container): TicketingProvider {
                $config = $container->get('config')['ticketing']['ticketmatic'] ?? [];

                // The lookup runs while a change is being saved, so a slow Ticketmatic must not block it for long.
                return new HttpTicketmaticProvider(
                    new Client(['connect_timeout' => 2, 'timeout' => 5]),
                    $config['api_base_url'] ?? '',
                    $config['bearer_token'] ?? '',
                    LoggerFactory::create($container, LoggerName::forService('ticketing', 'ticketmatic'))
                );
            }
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
