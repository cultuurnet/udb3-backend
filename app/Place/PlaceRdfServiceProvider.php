<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Place\ReadModel\RDF\RdfProjector;
use CultuurNet\UDB3\RDF\GraphStoreRepository;
use CultuurNet\UDB3\RDF\MainLanguageRepository;
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
                $this->container->get(MainLanguageRepository::class),
                new GraphStoreRepository(
                    new GraphStore(
                        rtrim($this->container->get('config')['placesGraphStoreUrl'], '/')
                    )
                ),
                new CallableIriGenerator(
                    fn (string $item): string =>
                        rtrim($this->container->get('config')['rdfBaseUri'], '/') . '/locaties/' . $item
                )
            )
        );
    }

}
