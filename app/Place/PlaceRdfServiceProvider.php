<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use CultuurNet\UDB3\Address\AddressParser;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Place\ReadModel\RDF\RdfProjector;
use CultuurNet\UDB3\RDF\CacheGraphRepository;
use CultuurNet\UDB3\RDF\MainLanguageRepository;
use CultuurNet\UDB3\RDF\RdfServiceProvider;

final class PlaceRdfServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [ RdfProjector::class ];
    }

    public function register(): void
    {
        $graphStoreRepository = RdfServiceProvider::createGraphStoreRepository(
            $this->container->get('config')['rdf']['placesGraphStoreUrl']
        );

        if ($this->container->get('config')['rdf']['placesCacheGraphEnabled']) {
            $graphStoreRepository = new CacheGraphRepository($this->container->get('cache')('rdf_place'));
        }

        $this->container->addShared(
            RdfProjector::class,
            fn (): RdfProjector => new RdfProjector(
                $this->container->get(MainLanguageRepository::class),
                $graphStoreRepository,
                RdfServiceProvider::createIriGenerator($this->container->get('config')['rdf']['placesRdfBaseUri']),
                $this->container->get(AddressParser::class)
            )
        );
    }
}
