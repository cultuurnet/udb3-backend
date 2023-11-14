<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use CultuurNet\UDB3\Address\AddressParser;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Error\LoggerFactory;
use CultuurNet\UDB3\Error\LoggerName;
use CultuurNet\UDB3\Model\Serializer\Place\PlaceDenormalizer;
use CultuurNet\UDB3\Place\ReadModel\RDF\RdfProjector;
use CultuurNet\UDB3\RDF\CacheGraphRepository;
use CultuurNet\UDB3\RDF\RdfServiceProvider;

final class PlaceRdfServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            RdfProjector::class,
        ];
    }

    public function register(): void
    {
        $graphStoreRepository = RdfServiceProvider::createGraphStoreRepository(
            $this->container->get('config')['rdf']['placesGraphStoreUrl'],
            $this->container->get('config')['rdf']['useDeleteAndInsert'] ?? false
        );

        if ($this->container->get('config')['rdf']['useCache']) {
            $graphStoreRepository = new CacheGraphRepository($this->container->get('cache')('rdf_place'));
        }

        $this->container->addShared(
            RdfProjector::class,
            fn (): RdfProjector => new RdfProjector(
                $graphStoreRepository,
                RdfServiceProvider::createIriGenerator($this->container->get('config')['rdf']['placesRdfBaseUri']),
                RdfServiceProvider::createIriGenerator($this->container->get('config')['taxonomy']['terms']),
                $this->container->get('place_jsonld_repository'),
                new PlaceDenormalizer(),
                $this->container->get(AddressParser::class),
                LoggerFactory::create($this->getContainer(), LoggerName::forService('rdf'))
            )
        );
    }
}
