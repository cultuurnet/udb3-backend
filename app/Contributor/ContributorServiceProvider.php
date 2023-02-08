<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Contributor;

use CultuurNet\UDB3\Container\AbstractServiceProvider;

final class ContributorServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            DbalContributorRepository::class,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            DbalContributorRepository::class,
            fn () => new DbalContributorRepository($container->get('dbal_connection'))
        );
    }
}
