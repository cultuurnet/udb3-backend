<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Contributor;

use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\Container\AbstractServiceProvider;

final class ContributorServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            ContributorRepository::class,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            ContributorRepository::class,
            fn () => new BroadcastingContributorRepository(
                new DbalContributorRepository($container->get('dbal_connection')),
                $container->get(EventBus::class),
                new ProjectedToJSONLDFactory(
                    $container->get('event_iri_generator'),
                    $container->get('place_iri_generator'),
                    $container->get('organizer_iri_generator')
                )
            )
        );
    }
}
