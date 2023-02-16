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
                new ContributorsUpdatedFactory(
                    new CallableIriGenerator(
                        fn ($cdbid) => $container->get('config')['url'] . '/events/' . $cdbid . '/contributors'
                    ),
                    new CallableIriGenerator(
                        fn ($cdbid) => $container->get('config')['url'] . '/places/' . $cdbid . '/contributors'
                    ),
                    new CallableIriGenerator(
                        fn () => new CallableIriGenerator(
                            fn ($cdbid) => $container->get('config')['url'] . '/organizers/' . $cdbid . '/contributors'
                        )
                    )
                )
            )
        );
    }
}
