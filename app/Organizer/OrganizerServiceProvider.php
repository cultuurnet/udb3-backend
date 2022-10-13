<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer;

use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\AggregateType;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\EventSourcing\DBAL\UniqueDBALEventStoreDecorator;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Offer\OfferLocator;
use CultuurNet\UDB3\OrganizerService;

final class OrganizerServiceProvider extends AbstractServiceProvider
{
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

        $container->addShared(
            'organizer_iri_generator',
            fn () => new CallableIriGenerator(
                fn ($cdbid) => $container->get('config')['url'] . '/organizers/' . $cdbid
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

        $container->addShared(
            'organizer_service',
            fn () => new OrganizerService(
                $container->get('organizer_jsonld_repository'),
                $container->get('organizer_repository'),
                $container->get('organizer_iri_generator'),
            )
        );
    }
}
