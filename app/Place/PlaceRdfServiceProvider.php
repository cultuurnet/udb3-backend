<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Place\ReadModel\RDF\RdfProjector;
use CultuurNet\UDB3\RDF\CacheMainLanguageRepository;
use CultuurNet\UDB3\RDF\GraphStoreRepository;
use EasyRdf\GraphStore;

final class PlaceRdfServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [RdfProjector::class];
    }

    public function register(): void
    {
        $this->container->addShared(
            RdfProjector::class,
            fn (): RdfProjector => new RdfProjector(
                new CacheMainLanguageRepository($this->container->get('cache')('rdf_main_language_place')),
                new GraphStoreRepository(
                    new GraphStore(
                        rtrim($this->container->get('config')['placesGraphStoreUrl'], '/')
                    )
                ),
                new CallableIriGenerator(
                    fn (string $item): string =>
                        rtrim($this->container->get('config')['rdfBaseUri'], '/') . $item
                )
            )
        );
    }

}
