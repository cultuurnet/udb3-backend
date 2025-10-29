<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer;

use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\AggregateType;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\EventSourcing\DBAL\UniqueDBALEventStoreDecorator;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Offer\OfferLocator;

final class OrganizerServiceProvider extends AbstractServiceProvider
{
    public const ORGANIZER_FRONTEND_IRI_GENERATOR = 'organizer_frontend_iri_generator';

    protected function getProvidedServiceNames(): array
    {
        return [
            'organizer_iri_generator',
            'organizer_store',
            'organizers_locator_event_stream_decorator',
            'organizer_repository',
            'organizer_service',
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        // Use if you want to generate a JSON url endpoint
        $container->addShared(
            'organizer_iri_generator',
            fn () => new CallableIriGenerator(
                fn ($cdbid) => $container->get('config')['url'] . '/organizers/' . $cdbid
            )
        );

        // Url to the user-friendly GUI version of an organizer
        $container->addShared(
            self::ORGANIZER_FRONTEND_IRI_GENERATOR,
            fn () => new CallableIriGenerator(
                fn ($cdbid) => $container->get('config')['frontend_url'] . '/organizers/' . $cdbid . '/ownerships'
            )
        );

        $container->addShared(
            'organizer_store',
            fn () => new UniqueDBALEventStoreDecorator(
                $container->get('event_store_factory')(AggregateType::organizer()),
                $container->get('dbal_connection'),
                'organizer_unique_websites',
                new WebsiteUniqueConstraintService(new WebsiteNormalizer())
            )
        );

        $container->addShared(
            'organizers_locator_event_stream_decorator',
            fn () => new OfferLocator($container->get('organizer_iri_generator'))
        );

        $container->addShared(
            'organizer_repository',
            fn () => new OrganizerRepository(
                $container->get('organizer_store'),
                $container->get(EventBus::class),
                [
                    $container->get('event_stream_metadata_enricher'),
                    $container->get('organizers_locator_event_stream_decorator'),
                ]
            )
        );
    }
}
