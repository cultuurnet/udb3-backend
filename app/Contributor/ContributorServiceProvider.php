<?php

namespace CultuurNet\UDB3\Contributor;

use CultuurNet\UDB3\Container\AbstractServiceProvider;

final class ContributorServiceProvider extends AbstractServiceProvider
{

    protected function getProvidedServiceNames(): array
    {
        return [
            ContributorRepository::class
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            ContributorRepository::class,
            fn () => new ContributorRepository($container->get('dbal_connection'))
        );
    }
}