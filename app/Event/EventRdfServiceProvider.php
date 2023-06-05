<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use CultuurNet\UDB3\Address\AddressParser;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Event\ReadModel\RDF\CacheLocationIdRepository;
use CultuurNet\UDB3\Event\ReadModel\RDF\GranularRdfProjector;
use CultuurNet\UDB3\RDF\CacheGraphRepository;
use CultuurNet\UDB3\RDF\MainLanguageRepository;
use CultuurNet\UDB3\RDF\RdfServiceProvider;
use CultuurNet\UDB3\UDB2\UDB2EventServicesProvider;

final class EventRdfServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            GranularRdfProjector::class,
        ];
    }

    public function register(): void
    {
        $graphStoreRepository = RdfServiceProvider::createGraphStoreRepository(
            $this->container->get('config')['rdf']['eventsGraphStoreUrl']
        );

        if ($this->container->get('config')['rdf']['eventsCacheGraphEnabled']) {
            $graphStoreRepository = new CacheGraphRepository($this->container->get('cache')('rdf_event'));
        }

        $this->container->addShared(
            GranularRdfProjector::class,
            fn (): GranularRdfProjector => new GranularRdfProjector(
                $this->container->get(MainLanguageRepository::class),
                $graphStoreRepository,
                new CacheLocationIdRepository($this->container->get('cache')('rdf_location_id')),
                RdfServiceProvider::createIriGenerator($this->container->get('config')['rdf']['eventsRdfBaseUri']),
                RdfServiceProvider::createIriGenerator($this->container->get('config')['rdf']['placesRdfBaseUri']),
                RdfServiceProvider::createIriGenerator($this->container->get('config')['taxonomy']['terms']),
                $this->container->get(AddressParser::class),
                UDB2EventServicesProvider::buildMappingServiceForPlaces(),
            )
        );
    }
}
