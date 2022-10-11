<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Iri\CallableIriGenerator;

class PlaceServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            'place_iri_generator',
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
    }
}
