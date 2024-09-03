<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\AggregateType;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Event\ReadModel\Relations\EventRelationsRepository;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\Doctrine\DBALReadRepository;
use CultuurNet\UDB3\Offer\OfferLocator;
use CultuurNet\UDB3\Place\Canonical\CanonicalService;
use CultuurNet\UDB3\Place\Canonical\DBALDuplicatePlaceRepository;
use CultuurNet\UDB3\Place\Canonical\DBALDuplicatePlacesRemovedFromClusterRepository;
use CultuurNet\UDB3\Place\Canonical\DuplicatePlaceRemovedFromClusterRepository;
use CultuurNet\UDB3\Place\ReadModel\Relations\PlaceRelationsRepository;

final class PlaceServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            'place_iri_generator',
            'place_store',
            'places_locator_event_stream_decorator',
            'place_repository',
            PlaceRepository::class,
            'place_service',
            'duplicate_place_repository',
            DuplicatePlaceRemovedFromClusterRepository::class,
            'canonical_service',
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            'place_iri_generator',
            fn () => new CallableIriGenerator(
                fn ($cdbid) => $container->get('config')['url'] . '/place/' . $cdbid
            )
        );

        $container->addShared(
            'place_store',
            fn () => $container->get('event_store_factory')(AggregateType::place())
        );

        $container->addShared(
            'places_locator_event_stream_decorator',
            fn () => new OfferLocator($container->get('place_iri_generator'))
        );

        // @todo: remove usages of 'place_repository' with Class based share
        $container->addShared(
            'place_repository',
            fn () => new PlaceRepository(
                $container->get('place_store'),
                $container->get(EventBus::class),
                [
                    $container->get('event_stream_metadata_enricher'),
                    $container->get('places_locator_event_stream_decorator'),
                ]
            )
        );
        $container->addShared(
            PlaceRepository::class,
            function () use ($container): PlaceRepository {
                return $container->get('place_repository');
            }
        );

        $container->addShared(
            'place_service',
            fn () => new LocalPlaceService(
                $container->get('place_jsonld_repository'),
                $container->get('place_repository'),
                $container->get(PlaceRelationsRepository::class),
                $container->get('place_iri_generator'),
            )
        );

        $container->addShared(
            'duplicate_place_repository',
            fn () => new DBALDuplicatePlaceRepository($container->get('dbal_connection'))
        );

        $container->addShared(
            DuplicatePlaceRemovedFromClusterRepository::class,
            fn () => new DBALDuplicatePlacesRemovedFromClusterRepository($container->get('dbal_connection'))
        );

        $container->addShared(
            'canonical_service',
            fn () => new CanonicalService(
                $container->get('config')['museumpas']['label'],
                $container->get('duplicate_place_repository'),
                $container->get(EventRelationsRepository::class),
                new DBALReadRepository(
                    $container->get('dbal_connection'),
                    'labels_relations'
                ),
                $container->get('place_jsonld_repository'),
            )
        );
    }
}
