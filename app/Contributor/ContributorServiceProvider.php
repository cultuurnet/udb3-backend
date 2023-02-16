<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Contributor;

use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Iri\CallableIriGenerator;

final class ContributorServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            'event_contributors_iri_generator',
            'place_contributors_iri_generator',
            'organizer_contributors_iri_generator',
            ContributorRepository::class,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            'event_contributors_iri_generator',
            fn () => new CallableIriGenerator(
                fn ($cdbid) => $container->get('config')['url'] . '/events/' . $cdbid . '/contributors'
            )
        );

        $container->addShared(
            'place_contributors_iri_generator',
            fn () => new CallableIriGenerator(
                fn ($cdbid) => $container->get('config')['url'] . '/places/' . $cdbid . '/contributors'
            )
        );

        $container->addShared(
            'organizer_contributors_iri_generator',
            fn () => new CallableIriGenerator(
                fn ($cdbid) => $container->get('config')['url'] . '/organizers/' . $cdbid . '/contributors'
            )
        );

        $container->addShared(
            ContributorRepository::class,
            fn () => new BroadcastingContributorRepository(
                new DbalContributorRepository($container->get('dbal_connection')),
                $container->get(EventBus::class)
            )
        );
    }
}
