<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\RDF;

use CultuurNet\UDB3\Container\AbstractServiceProvider;

final class RdfServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [MainLanguageRepository::class];
    }

    public function register(): void
    {
        $this->container->addShared(
            MainLanguageRepository::class,
            fn (): MainLanguageRepository => new CacheMainLanguageRepository(
                $this->container->get('cache')('rdf_main_language')
            )
        );
    }
}
